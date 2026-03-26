<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VideoService
{
    public function getVideoDuration(string $filePath): ?int
    {
        $fullPath = Storage::disk('local')->path($filePath);

        if (! file_exists($fullPath)) {
            return null;
        }

        $command = sprintf(
            'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>/dev/null',
            escapeshellarg($fullPath)
        );

        $output = shell_exec($command);

        if ($output === null || trim($output) === '') {
            Log::warning("Failed to get video duration for: {$filePath}");

            return null;
        }

        $duration = (int) round((float) trim($output));

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
            Log::warning("Failed to get video file size for: {$filePath}");

            return null;
        }

        return (int) $size;
    }

    public function updateMetadata(Video $video): ?array
    {
        if (! $video->file_identifier) {
            return null;
        }

        $duration = $this->getVideoDuration($video->file_identifier);
        $fileSize = $this->getVideoFileSize($video->file_identifier);

        if ($duration !== null) {
            $video->duration = $duration;
        }

        if ($fileSize !== null) {
            $video->file_size = $fileSize;
        }

        if ($duration !== null || $fileSize !== null) {
            $video->save();
        }

        return [
            'duration' => $video->duration,
            'file_size' => $video->file_size,
        ];
    }

    public function checkAndUpdateMetadata(Video $video): ?array
    {
        if (! $video->file_identifier) {
            return null;
        }

        $newDuration = $this->getVideoDuration($video->file_identifier);
        $newFileSize = $this->getVideoFileSize($video->file_identifier);
        $shouldSave = false;

        if ($newDuration !== null && $newDuration !== $video->duration) {
            $video->duration = $newDuration;
            $shouldSave = true;
        }

        if ($newFileSize !== null && $newFileSize !== $video->file_size) {
            $video->file_size = $newFileSize;
            $shouldSave = true;
        }

        if ($shouldSave) {
            $video->save();
        }

        return [
            'duration' => $video->duration,
            'file_size' => $video->file_size,
        ];
    }
}
