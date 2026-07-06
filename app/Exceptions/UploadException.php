<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown for user-facing upload errors. The message is safe to return to the
 * client; any other exception during an upload is treated as unexpected,
 * logged, and reported to the user with a generic message.
 */
class UploadException extends RuntimeException {}
