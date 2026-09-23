<?php

namespace App\Enums;

enum TranscodeStatus: int
{
    case PENDING = 1;
    case PROCESSING = 2;
    case COMPLIANT = 3;
    case TRANSCODED = 4;
    case FAILED = 5;

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::PROCESSING => 'Encodage en cours',
            self::COMPLIANT => 'Conforme',
            self::TRANSCODED => 'Encodée',
            self::FAILED => 'Échec',
        };
    }
}
