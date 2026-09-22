<?php

namespace App\Services\Media;

enum TranscodeAction: string
{
    /** Already compliant; nothing to do. */
    case SKIP = 'skip';

    /** Streams are compliant; only the container/faststart is wrong. */
    case REMUX = 'remux';

    /** Codec, profile, resolution, pixel format, or bitrate need fixing. */
    case ENCODE = 'encode';

    public function label(): string
    {
        return match ($this) {
            self::SKIP => 'Déjà conforme',
            self::REMUX => 'Recopié',
            self::ENCODE => 'Ré-encodé',
        };
    }
}
