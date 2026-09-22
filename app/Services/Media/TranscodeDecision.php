<?php

namespace App\Services\Media;

final readonly class TranscodeDecision
{
    public function __construct(
        public TranscodeAction $action,
        public string $reason,
    ) {}
}
