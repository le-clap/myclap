<?php

namespace App\Enums;

enum UploadStatus: int
{
    case UPLOAD_END = 0;
    case UPLOAD_INIT = 1;
    case UPLOAD_NULL = 2;

    public function label(): string
    {
        return match ($this) {
            self::UPLOAD_END => 'Upload terminé',
            self::UPLOAD_INIT => 'Upload en cours',
            self::UPLOAD_NULL => 'Pas encore uploadé',
        };
    }
}
