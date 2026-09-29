<?php

namespace App\Services;

use App\Exceptions\TranscodeException;
use App\Services\Media\TranscodePolicy;
use App\Services\Media\VideoProbe;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class TranscodeService
{
    private const string TEMP_DIR = 'videos/.tmp';

    private const int ORPHAN_TTL_HOURS = 24;

    private const float FREE_SPACE_SAFETY_FACTOR = 1.3;

    public function __construct(
        private readonly VideoService $videoService,
        private readonly TranscodePolicy $policy,
    ) {}

    /**
     * A scratch path under videos/.tmp/ for this attempt's output.
     */
    public function tempPath(string $token): string
    {
        Storage::disk('local')->makeDirectory(self::TEMP_DIR);

        return self::TEMP_DIR.'/'.$token.'-'.Str::random(8).'.mp4';
    }

    /**
     * Repackage into MP4 without touching the encoded streams.
     */
    public function remux(string $inputRelativePath, string $outputRelativePath): void
    {
        $this->run($this->remuxCommand(
            Storage::disk('local')->path($inputRelativePath),
            Storage::disk('local')->path($outputRelativePath),
        ));
    }

    /**
     * Re-encode to H.264/AAC, with compliant resolution and bitrate.
     */
    public function encode(string $inputRelativePath, string $outputRelativePath, VideoProbe $source, ?callable $onProgress = null): void
    {
        $this->run($this->encodeCommand(
            Storage::disk('local')->path($inputRelativePath),
            Storage::disk('local')->path($outputRelativePath),
            $source,
        ), $onProgress);
    }

    /**
     * Re-probe the file we just produced and refuse to let a silently
     * truncated or broken encode ever reach the swap.
     */
    public function verify(string $outputRelativePath, VideoProbe $source): VideoProbe
    {
        $probe = $this->videoService->probe($outputRelativePath);

        if ($probe === null || ! $probe->hasVideoStream()) {
            throw new TranscodeException("Le fichier produit par ffmpeg n'est pas une vidéo valide.");
        }

        if ($source->duration > 0) {
            $drift = abs($probe->duration - $source->duration) / $source->duration;

            if ($drift > 0.02) {
                throw new TranscodeException(sprintf(
                    'Durée incohérente après transcodage (%.1fs -> %.1fs), le fichier produit est rejeté.',
                    $source->duration,
                    $probe->duration
                ));
            }
        }

        return $probe;
    }

    public function promote(string $relativePath): string
    {
        $finalPath = 'videos/'.Str::random(10).'.mp4';
        Storage::disk('local')->move($relativePath, $finalPath);

        return $finalPath;
    }

    public function discard(?string $relativePath): void
    {
        if ($relativePath !== null) {
            Storage::disk('local')->delete($relativePath);
        }
    }

    public function sweepOrphanedTempFiles(): void
    {
        if (! Storage::disk('local')->exists(self::TEMP_DIR)) {
            return;
        }

        $threshold = now()->subHours(self::ORPHAN_TTL_HOURS)->getTimestamp();

        foreach (Storage::disk('local')->files(self::TEMP_DIR) as $file) {
            if (Storage::disk('local')->lastModified($file) < $threshold) {
                Storage::disk('local')->delete($file);
                Log::info("Transcode: swept orphaned temp file {$file}");
            }
        }
    }

    /**
     * Refuse to start an encode that could fill the disk.
     */
    public function ensureFreeSpace(int $sourceSizeBytes): void
    {
        $root = Storage::disk('local')->path('');
        $free = @disk_free_space($root);
        $needed = (int) ($sourceSizeBytes * self::FREE_SPACE_SAFETY_FACTOR);

        if ($free === false || $free < $needed) {
            throw new TranscodeException(sprintf(
                'Espace disque insuffisant pour le transcodage (%.2f Go libres, %.2f Go requis).',
                ($free === false ? 0 : $free) / 1_000_000_000,
                $needed / 1_000_000_000
            ));
        }
    }

    /**
     * @return array<int, string>
     */
    private function remuxCommand(string $inputFullPath, string $outputFullPath): array
    {
        return [
            config('media.ffmpeg_path', 'ffmpeg'),
            '-nostdin', '-y', '-v', 'error',
            '-i', $inputFullPath,
            '-map', '0:v:0', '-map', '0:a:0?', '-dn', '-sn',
            '-c', 'copy',
            '-movflags', '+faststart',
            '-f', 'mp4',
            $outputFullPath,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function encodeCommand(string $inputFullPath, string $outputFullPath, VideoProbe $source): array
    {
        $encode = config('media.encode');
        $compliance = config('media.compliance');

        $ceiling = $this->policy->bitrateCeilingForHeight(
            min($source->height ?? $compliance['max_height'], $compliance['max_height'])
        );

        $scale = sprintf(
            'scale=w=min(iw\,%d):h=min(ih\,%d):force_original_aspect_ratio=decrease:force_divisible_by=2',
            $compliance['max_width'],
            $compliance['max_height']
        );

        return [
            config('media.ffmpeg_path', 'ffmpeg'),
            '-nostdin', '-y', '-v', 'error', '-progress', 'pipe:1', '-nostats',
            '-i', $inputFullPath,
            '-map', '0:v:0', '-map', '0:a:0?', '-dn', '-sn',
            '-vf', $scale,
            '-c:v', 'libx264',
            '-preset', $encode['preset'],
            '-crf', (string) $encode['crf'],
            '-maxrate', (string) $ceiling,
            '-bufsize', (string) ($ceiling * 2),
            '-profile:v', $encode['profile'],
            '-level', $encode['level'],
            '-pix_fmt', $compliance['pix_fmt'],
            '-c:a', 'aac',
            '-b:a', $encode['audio_bitrate'],
            '-ac', (string) $encode['audio_channels'],
            '-ar', (string) $encode['audio_sample_rate'],
            '-movflags', '+faststart',
            '-max_muxing_queue_size', '1024',
            '-f', 'mp4',
            $outputFullPath,
        ];
    }

    /**
     * @param  array<int, string>  $command
     */
    private function run(array $command, ?callable $onProgress = null): void
    {
        $process = new Process($command);
        $process->setTimeout(null);

        try {
            $process->mustRun(function (string $type, string $buffer) use ($onProgress) {
                if ($onProgress !== null && $type === Process::OUT) {
                    $onProgress($buffer);
                }
            });
        } catch (\Throwable $e) {
            throw new TranscodeException('ffmpeg a échoué : '.$e->getMessage(), previous: $e);
        }
    }
}
