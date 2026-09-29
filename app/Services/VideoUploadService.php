<?php

namespace App\Services;

use App\Enums\TranscodeStatus;
use App\Enums\UploadStatus;
use App\Exceptions\UploadException;
use App\Jobs\TranscodeVideoJob;
use App\Models\Video;
use App\Models\VideoUpload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoUploadService
{
    public function __construct(
        private readonly VideoService $videoService
    ) {}

    public function initUpload(Video $video, string $fileName, int $fileSize, string $username): array
    {
        if ($video->upload_status === UploadStatus::UPLOAD_END) {
            throw new UploadException('Vidéo déjà uploadée');
        }

        if ($fileSize > config('media.max_video_upload_size')) {
            throw new UploadException('La taille du fichier dépasse la limite autorisée.');
        }

        $upload = $video->upload;
        $startIndex = 0;

        if ($upload) {
            // Resume upload
            if ($upload->file_size != $fileSize) {
                throw new UploadException('Le fichier doit être identique à celui que vous aviez commencé à envoyer.');
            }

            if (Storage::disk('local')->exists($upload->file_identifier)) {
                $path = Storage::disk('local')->path($upload->file_identifier);
                clearstatcache(true, $path);
                $startIndex = filesize($path);
            } else {
                // Recreate the file
                Storage::disk('local')->put($upload->file_identifier, '');
            }
        } else {
            // Create new upload
            $fileIdentifier = 'video_upload/'.Str::random(40);
            Storage::disk('local')->put($fileIdentifier, '');

            $upload = VideoUpload::create([
                'video_token' => $video->token,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'file_identifier' => $fileIdentifier,
                'created_by' => $username,
            ]);

            $video->upload_status = UploadStatus::UPLOAD_INIT;
            $video->save();
        }

        return [
            'startIndex' => $startIndex,
            'chunkSize' => config('media.upload_chunk_size'),
        ];
    }

    public function processChunk(Video $video, $chunkFile, int $startIndex): array
    {
        $upload = $video->upload()->firstOrFail();

        $path = Storage::disk('local')->path($upload->file_identifier);
        $currentSize = $this->fileSizeOrZero($path);

        if ($startIndex !== $currentSize) {
            throw new UploadException("Index incorrect : attendu {$currentSize}, reçu {$startIndex}");
        }

        // Read and append the chunk
        $chunkContent = file_get_contents($chunkFile->getRealPath());

        if ($chunkContent === false) {
            throw new UploadException('Impossible de lire le chunk envoyé.');
        }

        $chunkLength = strlen($chunkContent);
        $expectedLength = min(config('media.upload_chunk_size'), $upload->file_size - $startIndex);

        if ($chunkLength !== $expectedLength) {
            throw new UploadException("Taille de chunk invalide : attendu {$expectedLength} octets, reçu {$chunkLength}.");
        }

        $written = file_put_contents($path, $chunkContent, FILE_APPEND);

        if ($written === false) {
            throw new UploadException("Erreur lors de l'écriture du chunk");
        }

        $newSize = $startIndex + $written;

        return [
            'completed' => $newSize >= $upload->file_size,
            'startIndex' => $newSize,
        ];
    }

    public function finalizeUpload(Video $video): void
    {
        $upload = $video->upload()->firstOrFail();

        $tempPath = $upload->file_identifier;
        $path = Storage::disk('local')->path($tempPath);

        // The whole declared payload must have been received
        if ($this->fileSizeOrZero($path) !== $upload->file_size) {
            throw new UploadException("Toute la ressource n'a pas été correctement téléversée. Veuillez recommencer.");
        }

        $probe = $this->videoService->probe($tempPath);
        if ($probe === null || ! $probe->hasVideoStream()) {
            Storage::disk('local')->delete($tempPath);
            $upload->delete();
            $video->upload_status = UploadStatus::UPLOAD_NULL;
            $video->save();

            throw new UploadException("Le fichier envoyé n'est pas une vidéo valide.");
        }

        $finalIdentifier = 'videos/'.Str::random(10).'.mp4';

        Storage::disk('local')->makeDirectory('videos');
        Storage::disk('local')->move($tempPath, $finalIdentifier);

        try {
            DB::transaction(function () use ($video, $upload, $finalIdentifier, $probe) {
                $video->file_identifier = $finalIdentifier;
                $video->upload_status = UploadStatus::UPLOAD_END;
                $video->uploaded_on = now();
                $video->duration = (int) round($probe->duration);
                $video->file_size = $upload->file_size;
                $video->video_codec = $probe->videoCodec;
                $video->audio_codec = $probe->audioCodec;
                $video->width = $probe->width;
                $video->height = $probe->height;
                $video->bitrate = $probe->videoBitrate;
                $video->faststart = $probe->faststart;
                $video->save();

                $upload->delete();
            });
        } catch (\Throwable $e) {
            if (Storage::disk('local')->exists($finalIdentifier)) {
                Storage::disk('local')->move($finalIdentifier, $tempPath);
            }

            throw $e;
        }

        $attempt = $video->transcodeAttempts()->create([
            'status' => TranscodeStatus::PENDING,
            'started_on' => now(),
        ]);

        try {
            TranscodeVideoJob::dispatch($video, $attempt);
        } catch (\Throwable $e) {
            Log::error('Failed to dispatch TranscodeVideoJob after upload finalize', [
                'video' => $video->token,
                'exception' => $e,
            ]);

            $attempt->update([
                'status' => TranscodeStatus::FAILED,
                'error' => 'La mise en file pour vérification a échoué.',
                'finished_on' => now(),
            ]);
        }
    }

    public function resetUpload(Video $video): void
    {
        $upload = $video->upload;

        if ($upload) {
            Storage::disk('local')->delete($upload->file_identifier);
            $upload->delete();
        }

        $video->upload_status = UploadStatus::UPLOAD_NULL;
        $video->save();
    }

    public function getUploadProgress(Video $video): ?array
    {
        $upload = $video->upload;

        if (! $upload) {
            return null;
        }

        $currentSize = $this->fileSizeOrZero(Storage::disk('local')->path($upload->file_identifier));

        return [
            'fileName' => $upload->file_name,
            'fileSize' => $upload->file_size,
            'uploadedSize' => $currentSize,
            'percentage' => $upload->file_size > 0 ? round(($currentSize / $upload->file_size) * 100, 2) : 0,
        ];
    }

    /**
     * clearstatcache() is required here: PHP caches filesize() per path for
     * the request, but these paths are being written to (chunk-by-chunk, or
     * concurrently by another request polling progress) within the same
     * request lifecycle.
     */
    private function fileSizeOrZero(string $fullPath): int
    {
        clearstatcache(true, $fullPath);

        return file_exists($fullPath) ? filesize($fullPath) : 0;
    }
}
