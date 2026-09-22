<?php

namespace App\Services;

use App\Models\Video;
use App\Services\Media\VideoProbe;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class VideoService
{
    /**
     * Run ffprobe and return a full VideoProbe (codec, resolution, measured
     * bitrate, faststart, ...), or null if the file is missing or ffprobe
     * can't make sense of it (used as the "is this a real, decodable video"
     * gate at upload finalize).
     */
    public function probe(string $filePath): ?VideoProbe
    {
        $fullPath = Storage::disk('local')->path($filePath);

        if (! file_exists($fullPath)) {
            return null;
        }

        $process = new Process([
            config('media.ffprobe_path', 'ffprobe'),
            '-v', 'error',
            '-print_format', 'json',
            '-show_format',
            '-show_streams',
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

        try {
            $json = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            Log::warning("ffprobe returned invalid JSON for {$filePath}: {$e->getMessage()}");

            return null;
        }

        return VideoProbe::fromFfprobeJson($json, $this->detectFaststart($fullPath));
    }

    public function getVideoDuration(string $filePath): ?int
    {
        $probe = $this->probe($filePath);

        if ($probe === null || ! $probe->hasVideoStream() || $probe->duration <= 0) {
            return null;
        }

        return (int) round($probe->duration);
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

        $probe = $this->probe($video->file_identifier);

        $video->duration = $probe?->hasVideoStream() ? (int) round($probe->duration) : null;
        $video->file_size = $this->getVideoFileSize($video->file_identifier);

        $video->video_codec = $probe?->videoCodec;
        $video->audio_codec = $probe?->audioCodec;
        $video->width = $probe?->width;
        $video->height = $probe?->height;
        $video->bitrate = $probe?->videoBitrate;
        $video->faststart = $probe?->faststart;

        if ($video->isDirty()) {
            $video->save();
        }
    }

    /**
     * ffprobe never reports whether an MP4/MOV's `moov` atom (its index)
     * comes before or after `mdat` (the media data) — walk the top-level
     * ISO-BMFF boxes ourselves. A non-MP4 container (e.g. Matroska/WebM)
     * fails this walk almost immediately, which correctly reports false.
     */
    private function detectFaststart(string $fullPath): bool
    {
        $handle = @fopen($fullPath, 'rb');

        if ($handle === false) {
            return false;
        }

        try {
            for ($i = 0; $i < 64; $i++) {
                $header = fread($handle, 8);

                if ($header === false || strlen($header) < 8) {
                    return false;
                }

                ['size' => $size, 'type' => $type] = unpack('Nsize/a4type', $header);

                if (! preg_match('/^[a-zA-Z0-9]{4}$/', $type)) {
                    return false;
                }

                if ($type === 'moov') {
                    return true;
                }

                if ($type === 'mdat') {
                    return false;
                }

                if ($size === 1) {
                    // Size==1 means the real (64-bit) size follows as the
                    // next 8 bytes, counted from the start of this box.
                    $extended = fread($handle, 8);

                    if ($extended === false || strlen($extended) < 8) {
                        return false;
                    }

                    $size = unpack('J', $extended)[1]; // J = uint64, big-endian
                    $skip = $size - 16;
                } elseif ($size === 0) {
                    // Box extends to EOF (last box in the file) — moov isn't here.
                    return false;
                } else {
                    $skip = $size - 8;
                }

                if ($skip < 0 || fseek($handle, $skip, SEEK_CUR) !== 0) {
                    return false;
                }
            }

            return false;
        } finally {
            fclose($handle);
        }
    }
}
