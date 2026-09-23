<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Maximum Video Upload Size
    |--------------------------------------------------------------------------
    |
    | Hard ceiling (in bytes) for a single resumable video upload. The value
    | is enforced both when an upload is initialised and while chunks are
    | appended, protecting the shared NFS storage from exhaustion.
    |
    | Default: 100 GB.
    |
    */

    'max_video_upload_size' => (int) env('MAX_VIDEO_UPLOAD_SIZE', 100 * 1024 * 1024 * 1024),

    /*
    |--------------------------------------------------------------------------
    | Video Upload Chunk Size
    |--------------------------------------------------------------------------
    |
    | Fixed size (in bytes) of each chunk sent by the resumable video uploader.
    |
    */

    'upload_chunk_size' => (int) env('VIDEO_UPLOAD_CHUNK_SIZE', 5 * 1024 * 1024),

    /*
    |--------------------------------------------------------------------------
    | ffprobe Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum number of seconds a single ffprobe invocation may run before it
    | is killed. Prevents a malformed file from hanging a PHP-FPM worker.
    |
    */

    'ffprobe_timeout' => (int) env('FFPROBE_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Serve media via X-Accel-Redirect
    |--------------------------------------------------------------------------
    |
    | X-Accel-Redirect is an nginx-only directive (see nginx.conf's
    | `location ^~ /internal-storage/`): nginx intercepts it and streams the
    | real file itself.
    |
    */

    'serve_via_x_accel_redirect' => (bool) env('MEDIA_X_ACCEL_REDIRECT', true),

    /*
    |--------------------------------------------------------------------------
    | ffprobe / ffmpeg binaries
    |--------------------------------------------------------------------------
    */

    'ffprobe_path' => env('FFPROBE_PATH', 'ffprobe'),

    'ffmpeg_path' => env('FFMPEG_PATH', 'ffmpeg'),

    /*
    |--------------------------------------------------------------------------
    | Transcode Service
    |--------------------------------------------------------------------------
    |
    | Whether newly uploaded videos are held back (upload_status stays
    | UPLOAD_PROCESSING) until the async transcode job has probed them and
    | reached a terminal state (COMPLIANT/TRANSCODED). Disable only as an
    | operational kill switch (e.g. no worker running) — this restores the
    | previous behaviour of publishing immediately on finalize.
    |
    */

    'transcode_enabled' => (bool) env('TRANSCODE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Web-compliance thresholds
    |--------------------------------------------------------------------------
    |
    | Used by TranscodePolicy to decide SKIP / REMUX / ENCODE. These are
    | tolerance ceilings, not encode targets: a file that already satisfies
    | them is left untouched.
    |
    */

    'compliance' => [
        'containers' => ['mov,mp4,m4a,3gp,3g2,mj2'],

        'video_codec' => 'h264',
        'profiles' => ['Constrained Baseline', 'Baseline', 'Main', 'High'],
        'max_level' => 42, // 4.2 (covers 1080p60)
        'pix_fmt' => 'yuv420p',

        'max_width' => 1920,
        'max_height' => 1080,

        'audio_codec' => 'aac',
        'max_audio_channels' => 2,

        // Video-bitrate ceiling (bits/sec) as a function of height. A
        // compliant encode (CRF 21, medium) lands well under these at
        // typical resolutions; only genuinely wasteful sources trip them.
        'max_video_bitrate_by_height' => [
            2160 => 20_000_000,
            1080 => 10_000_000,
            720 => 6_000_000,
            480 => 3_000_000,
            360 => 1_500_000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Encode parameters
    |--------------------------------------------------------------------------
    */

    'encode' => [
        'preset' => 'medium',
        'crf' => 21,
        'profile' => 'high',
        'level' => '4.2',
        'audio_bitrate' => '128k',
        'audio_channels' => 2,
        'audio_sample_rate' => 48000,
    ],

];
