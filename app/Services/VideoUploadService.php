<?php

namespace App\Services;

use App\Enums\UploadStatus;
use App\Exceptions\UploadException;
use App\Models\Video;
use App\Models\VideoUpload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoUploadService
{
    private const MIN_CHUNK_SIZE = 0.1 * 1024 * 1024; // 0.1 MB

    private const MAX_CHUNK_SIZE = 10 * 1024 * 1024;  // 10 MB

    public function __construct(
        private readonly VideoService $videoService
    ) {}

    public function initUpload(Video $video, string $fileName, int $fileSize, string $username): array
    {
        if ($video->upload_status === UploadStatus::UPLOAD_END) {
            throw new UploadException('Vidéo déjà uploadée');
        }

        $upload = VideoUpload::where('video_token', $video->token)->first();
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
            'chunkSize' => (int) self::MIN_CHUNK_SIZE,
        ];
    }

    public function processChunk(Video $video, $chunkFile, int $startIndex, int $chunkSize): array
    {
        $upload = VideoUpload::where('video_token', $video->token)->firstOrFail();

        $path = Storage::disk('local')->path($upload->file_identifier);
        clearstatcache(true, $path);
        $currentSize = file_exists($path) ? filesize($path) : 0;

        if ($startIndex !== $currentSize) {
            throw new UploadException("Index incorrect : attendu {$currentSize}, reçu {$startIndex}");
        }

        // Read and append the chunk
        $chunkContent = file_get_contents($chunkFile->getRealPath());
        $chunkLength = strlen($chunkContent);

        if ($startIndex + $chunkLength > $upload->file_size) {
            throw new UploadException('La taille du fichier dépasse la taille annoncée.');
        }

        $written = file_put_contents($path, $chunkContent, FILE_APPEND);

        if ($written === false) {
            throw new UploadException("Erreur lors de l'écriture du chunk");
        }

        $newSize = $startIndex + $written;

        if ($newSize >= $upload->file_size) {
            return [
                'completed' => true,
                'startIndex' => $newSize,
                'chunkSize' => $chunkSize,
            ];
        }

        // Clamp the next chunk size to the accepted range
        $adaptiveChunkSize = min(
            (int) self::MAX_CHUNK_SIZE,
            max((int) self::MIN_CHUNK_SIZE, $chunkSize)
        );

        return [
            'completed' => false,
            'startIndex' => $newSize,
            'chunkSize' => $adaptiveChunkSize,
        ];
    }

    public function finalizeUpload(Video $video): void
    {
        $upload = VideoUpload::where('video_token', $video->token)->firstOrFail();

        $tempPath = $upload->file_identifier;
        $path = Storage::disk('local')->path($tempPath);

        // The whole declared payload must have been received
        clearstatcache(true, $path);
        if (! file_exists($path) || filesize($path) !== $upload->file_size) {
            throw new UploadException("Toute la ressource n'a pas été correctement téléversée. Veuillez recommencer.");
        }

        // Validate the assembled file is actually a decodable video before we
        // commit it. Doubles as the single metadata probe for this upload.
        $duration = $this->videoService->getVideoDuration($tempPath);
        if ($duration === null) {
            Storage::disk('local')->delete($tempPath);
            $upload->delete();
            $video->upload_status = UploadStatus::UPLOAD_NULL;
            $video->save();

            throw new UploadException("Le fichier envoyé n'est pas une vidéo valide.");
        }

        $finalIdentifier = 'videos/'.Str::random(10).'.mp4';

        // Ensure videos directory exists
        $videosDir = Storage::disk('local')->path('videos');
        if (! is_dir($videosDir)) {
            mkdir($videosDir, 0755, true);
        }

        // Move the assembled file into place, then commit the DB changes
        // atomically. If the transaction fails, roll the file move back so the
        // upload can be retried cleanly.
        Storage::disk('local')->move($tempPath, $finalIdentifier);

        try {
            DB::transaction(function () use ($video, $upload, $finalIdentifier, $duration) {
                $video->file_identifier = $finalIdentifier;
                $video->upload_status = UploadStatus::UPLOAD_END;
                $video->uploaded_on = now();
                $video->duration = $duration;
                $video->file_size = $upload->file_size;
                $video->save();

                $upload->delete();
            });
        } catch (\Throwable $e) {
            if (Storage::disk('local')->exists($finalIdentifier)) {
                Storage::disk('local')->move($finalIdentifier, $tempPath);
            }

            throw $e;
        }
    }

    public function resetUpload(Video $video): void
    {
        $upload = VideoUpload::where('video_token', $video->token)->first();

        if ($upload) {
            if (Storage::disk('local')->exists($upload->file_identifier)) {
                Storage::disk('local')->delete($upload->file_identifier);
            }
            $upload->delete();
        }

        $video->upload_status = UploadStatus::UPLOAD_NULL;
        $video->save();
    }

    public function getUploadProgress(Video $video): ?array
    {
        $upload = VideoUpload::where('video_token', $video->token)->first();

        if (! $upload) {
            return null;
        }

        $path = Storage::disk('local')->path($upload->file_identifier);
        clearstatcache(true, $path);
        $currentSize = file_exists($path) ? filesize($path) : 0;

        return [
            'fileName' => $upload->file_name,
            'fileSize' => $upload->file_size,
            'uploadedSize' => $currentSize,
            'percentage' => $upload->file_size > 0 ? round(($currentSize / $upload->file_size) * 100, 2) : 0,
        ];
    }
}
