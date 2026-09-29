<?php

namespace App\Services\Media;

final readonly class VideoProbe
{
    public function __construct(
        public string $formatName,
        public float $duration,
        public int $size,
        public ?string $videoCodec,
        public ?string $profile,
        public ?int $level,
        public ?string $pixFmt,
        public ?int $width,
        public ?int $height,
        public ?int $videoBitrate,
        public ?string $audioCodec,
        public ?int $audioChannels,
        public bool $hasAudio,
        public bool $faststart,
    ) {}

    /**
     * @param  array<string, mixed>  $ffprobeJson  Decoded `ffprobe -show_format -show_streams` output.
     */
    public static function fromFfprobeJson(array $ffprobeJson, bool $faststart): self
    {
        $format = $ffprobeJson['format'] ?? [];
        $streams = $ffprobeJson['streams'] ?? [];

        $video = self::firstStream($streams, 'video');
        $audio = self::firstStream($streams, 'audio');

        $totalBitrate = self::toInt($format['bit_rate'] ?? null);
        $audioBitrate = $audio ? self::toInt($audio['bit_rate'] ?? null) : null;

        $videoBitrate = $video ? self::toInt($video['bit_rate'] ?? null) : null;
        if ($videoBitrate === null && $totalBitrate !== null) {
            // Container didn't report a per-stream rate (common for
            // Matroska/WebM): approximate video-only bitrate from the
            // overall rate minus whatever the audio stream is using.
            $videoBitrate = max(0, $totalBitrate - ($audioBitrate ?? 0));
        }

        return new self(
            formatName: (string) ($format['format_name'] ?? ''),
            duration: (float) ($format['duration'] ?? 0.0),
            size: self::toInt($format['size'] ?? null) ?? 0,
            videoCodec: $video['codec_name'] ?? null,
            profile: $video['profile'] ?? null,
            level: $video ? self::toInt($video['level'] ?? null) : null,
            pixFmt: $video['pix_fmt'] ?? null,
            width: $video ? self::toInt($video['width'] ?? null) : null,
            height: $video ? self::toInt($video['height'] ?? null) : null,
            videoBitrate: $videoBitrate,
            audioCodec: $audio['codec_name'] ?? null,
            audioChannels: $audio ? self::toInt($audio['channels'] ?? null) : null,
            hasAudio: $audio !== null,
            faststart: $faststart,
        );
    }

    public function hasVideoStream(): bool
    {
        return $this->videoCodec !== null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $streams
     */
    private static function firstStream(array $streams, string $type): ?array
    {
        return array_find($streams, fn ($stream) => ($stream['codec_type'] ?? null) === $type);
    }

    private static function toInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) round((float) $value);
    }
}
