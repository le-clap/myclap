<?php

namespace App\Jobs;

use App\Enums\TranscodeStatus;
use App\Enums\UploadStatus;
use App\Exceptions\TranscodeException;
use App\Models\Video;
use App\Models\VideoTranscode;
use App\Services\Media\TranscodeAction;
use App\Services\Media\TranscodePolicy;
use App\Services\Media\VideoProbe;
use App\Services\TranscodeService;
use App\Services\VideoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Probes a video, decides SKIP/REMUX/ENCODE, and swaps the file pointer once
 * the result is verified.
 */
class TranscodeVideoJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    /** Encodes are legitimately long; the worker's --max-time recycles the process instead. */
    public int $timeout = 0;

    /** Ceiling on the uniqueness lock in case a worker dies without releasing it. */
    public int $uniqueFor = 6 * 3600;

    private readonly string $videoToken;

    private readonly int $attemptId;

    public function __construct(Video $video, VideoTranscode $attempt)
    {
        $this->videoToken = $video->token;
        $this->attemptId = $attempt->id;
        $this->onQueue('transcode');
    }

    public function uniqueId(): string
    {
        return $this->videoToken;
    }

    public function handle(VideoService $videoService, TranscodeService $transcodeService, TranscodePolicy $policy): void
    {
        $video = Video::firstWhere('token', $this->videoToken);
        $attempt = VideoTranscode::find($this->attemptId);

        if ($video === null || $video->file_identifier === null || $attempt === null || $attempt->status !== TranscodeStatus::PENDING) {
            return;
        }

        if ($video->upload_status !== UploadStatus::UPLOAD_END) {
            return;
        }

        $startedFrom = $video->file_identifier;

        $attempt->status = TranscodeStatus::PROCESSING;
        $attempt->save();

        $transcodeService->sweepOrphanedTempFiles();
        $producedPath = null;

        try {
            $sourceSize = $videoService->getVideoFileSize($startedFrom) ?? 0;
            $transcodeService->ensureFreeSpace($sourceSize);

            $probe = $videoService->probe($startedFrom);

            if ($probe === null || ! $probe->hasVideoStream()) {
                throw new TranscodeException("Le fichier source n'est plus une vidéo valide.");
            }

            $decision = $policy->decide($probe);
            $attempt->action = $decision->action;
            $attempt->reason = $decision->reason;

            if ($decision->action === TranscodeAction::SKIP) {
                $this->commit($video, $startedFrom, $probe);
                $this->finish($attempt, TranscodeStatus::COMPLIANT);

                return;
            }

            $producedPath = $transcodeService->tempPath($video->token);

            if ($decision->action === TranscodeAction::REMUX) {
                $transcodeService->remux($startedFrom, $producedPath);
            } else {
                $transcodeService->encode($startedFrom, $producedPath, $probe, function (string $chunk) use ($video, $probe): void {
                    $this->reportProgress($video->token, $probe, $chunk);
                });
            }

            $resultProbe = $transcodeService->verify($producedPath, $probe);

            $producedPath = $transcodeService->promote($producedPath);
            $this->commit($video, $startedFrom, $resultProbe, $producedPath);

            // The video now points at the new file: never let the catch below delete it.
            $producedPath = null;

            $this->finish($attempt, TranscodeStatus::TRANSCODED);
            $transcodeService->discard($startedFrom);
            Cache::forget("transcode:{$video->token}:progress");
        } catch (\Throwable $e) {
            $transcodeService->discard($producedPath);

            $attempt->status = TranscodeStatus::FAILED;
            $attempt->error = $this->safeMessage($e);
            $attempt->finished_on = now();
            $attempt->save();

            Log::error('Transcode failed', ['video' => $this->videoToken, 'exception' => $e]);
        }
    }

    private function commit(Video $video, string $startedFrom, VideoProbe $probe, ?string $newFileIdentifier = null): void
    {
        $attributes = [
            'video_codec' => $probe->videoCodec,
            'audio_codec' => $probe->audioCodec,
            'width' => $probe->width,
            'height' => $probe->height,
            'bitrate' => $probe->videoBitrate,
            'faststart' => $probe->faststart,
        ];

        if ($newFileIdentifier !== null) {
            $attributes['file_identifier'] = $newFileIdentifier;
            $attributes['file_size'] = $probe->size;
            $attributes['duration'] = (int) round($probe->duration);
        }

        $updated = Video::where('id', $video->id)
            ->where('file_identifier', $startedFrom)
            ->update($attributes);

        if ($updated === 0) {
            throw new TranscodeException('La vidéo a changé pendant le traitement, résultat abandonné.');
        }
    }

    private function finish(VideoTranscode $attempt, TranscodeStatus $status): void
    {
        $attempt->status = $status;
        $attempt->error = null;
        $attempt->finished_on = now();
        $attempt->save();
    }

    private function reportProgress(string $videoToken, VideoProbe $probe, string $chunk): void
    {
        if ($probe->duration <= 0) {
            return;
        }

        foreach (explode("\n", $chunk) as $line) {
            if (! str_starts_with($line, 'out_time_us=')) {
                continue;
            }

            $outTimeSeconds = ((int) substr($line, strlen('out_time_us='))) / 1_000_000;
            $percent = (int) round(min(99, max(0, $outTimeSeconds / $probe->duration * 100)));

            Cache::put("transcode:{$videoToken}:progress", $percent, now()->addMinutes(30));
        }
    }

    public function failed(\Throwable $e): void
    {
        VideoTranscode::where('id', $this->attemptId)->update([
            'status' => TranscodeStatus::FAILED->value,
            'error' => $this->safeMessage($e),
            'finished_on' => now(),
        ]);
    }

    private function safeMessage(\Throwable $e): string
    {
        return $e instanceof TranscodeException
            ? $e->getMessage()
            : 'Erreur inattendue lors du transcodage.';
    }
}
