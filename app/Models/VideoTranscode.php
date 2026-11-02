<?php

namespace App\Models;

use App\Enums\TranscodeStatus;
use App\Services\Media\TranscodeAction;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property TranscodeAction|null $action
 * @property TranscodeStatus $status
 * @property Carbon|null $started_on
 * @property Carbon|null $finished_on
 */
class VideoTranscode extends Model
{
    protected $table = 'video_transcode';

    public $timestamps = false;

    protected $fillable = [
        'video_token',
        'action',
        'status',
        'reason',
        'error',
        'started_on',
        'finished_on',
    ];

    protected $appends = [
        'status_label',
        'action_label',
    ];

    protected function casts(): array
    {
        return [
            'action' => TranscodeAction::class,
            'status' => TranscodeStatus::class,
            'started_on' => 'datetime',
            'finished_on' => 'datetime',
        ];
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class, 'video_token', 'token');
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::make(get: fn () => $this->status->label());
    }

    protected function actionLabel(): Attribute
    {
        return Attribute::make(get: fn () => $this->action?->label());
    }
}
