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
    | ffprobe Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum number of seconds a single ffprobe invocation may run before it
    | is killed. Prevents a malformed file from hanging a PHP-FPM worker.
    |
    */

    'ffprobe_timeout' => (int) env('FFPROBE_TIMEOUT', 30),

];
