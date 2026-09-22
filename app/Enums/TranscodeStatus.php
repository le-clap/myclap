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

    /**
     * @return array<int, array{value: int, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }
}
