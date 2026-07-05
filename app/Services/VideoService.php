<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class VideoService
{
    public function getVideoDuration(string $filePath): ?int
    {
        $fullPath = Storage::disk('local')->path($filePath);

        if (! file_exists($fullPath)) {
            return null;
        }

        $process = new Process([
            'ffprobe',
            '-v', 'error',
            '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1',
            $fullPath,
        ]);
        $process->setTimeout((float) config('media.ffprobe_timeout', 30));

        try {
            $process->run();
        } catch (ProcessTimedOutException $e) {
            Log::warning("ffprobe timed out for: {$filePath}");

            return null;
        } catch (\Throwable $e) {
            Log::warning("ffprobe failed for {$filePath}: {$e->getMessage()}");

            return null;
        }

        if (! $process->isSuccessful()) {
            Log::warning("ffprobe unsuccessful for: {$filePath}");

            return null;
        }

        $output = trim($process->getOutput());

        if ($output === '') {
            return null;
        }

        $duration = (int) round((float) $output);

        return $duration > 0 ? $duration : null;
    }

    public function getVideoFileSize(string $filePath): ?int
    {
        $fullPath = Storage::disk('local')->path($filePath);

        if (! file_exists($fullPath)) {
            return null;
        }

        clearstatcache(true, $fullPath);

        $size = filesize($fullPath);

        if ($size === false) {
            Log::warning("Unable to read file size for: {$filePath}");

            return null;
        }

        return $size;
    }

    public function syncMetadata(Video $video): void
    {
        if (! $video->file_identifier) {
            return;
        }

        $fullPath = Storage::disk('local')->path($video->file_identifier);

        if (! file_exists($fullPath)) {
            Log::warning("Video file missing: {$video->file_identifier}");

            $video->duration = null;
            $video->file_size = null;

            if ($video->isDirty()) {
                $video->save();
            }

            return;
        }

        $video->duration = $this->getVideoDuration($video->file_identifier);
        $video->file_size = $this->getVideoFileSize($video->file_identifier);

        if ($video->isDirty()) {
            $video->save();
        }
    }
}
