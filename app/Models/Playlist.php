<?php

namespace App\Models;

use App\Enums\ContentAccess;
use App\Enums\PlaylistType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

class Playlist extends Model implements Sortable
{
    use SortableTrait;

    protected $table = 'playlist';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'banner_identifier',
        'type',
        'access',
        'position',
        'created_on',
        'modified_on',
        'modified_by',
    ];

    protected $appends = [
        'type_label',
        'access_label',
    ];

    protected function casts(): array
    {
        return [
            'created_on' => 'datetime',
            'modified_on' => 'datetime',
            'type' => 'integer',
            'access' => 'integer',
            'position' => 'integer',
        ];
    }

    public array $sortable = [
        'order_column_name' => 'position',
        'sort_when_creating' => true,
    ];

    public function scopeOrderedForDisplay(Builder $query): Builder
    {
        return $query
            ->orderByDesc('type')
            ->ordered()
            ->orderBy('name')
            ->orderBy('id');
    }

    public function buildSortQuery(): Builder
    {
        return static::query()->where('type', $this->type);
    }

    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(
            Video::class,
            'playlist_video',
            'playlist_slug',
            'video_token',
            'slug',
            'token'
        )->withPivot('position')->orderByPivot('position');
    }

    public function getVideosCollection(?User $user = null): Collection
    {
        $query = $this->videos()->published();

        if ($user) {
            $query->whereIn('access', [
                ContentAccess::CENTRALIENS->value,
                ContentAccess::PUBLIC->value,
                ContentAccess::UNLINKED->value,
            ]);
        } else {
            $query->whereIn('access', [
                ContentAccess::PUBLIC->value,
                ContentAccess::UNLINKED->value,
            ]);
        }

        return $query->get();
    }

    /**
     * Sync videos with their positions.
     *
     * @param  array  $tokens  Array of video tokens in order
     */
    public function syncVideosWithOrder(array $tokens): void
    {
        $syncData = [];
        foreach ($tokens as $position => $token) {
            $syncData[$token] = ['position' => $position];
        }

        $this->videos()->sync($syncData);
    }

    /**
     * Get video tokens in order.
     */
    public function getVideoTokens(): array
    {
        return $this->videos()->pluck('token')->toArray();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modified_by', 'username');
    }

    protected function typeEnum(): Attribute
    {
        return Attribute::make(
            get: fn () => PlaylistType::from($this->type),
        );
    }

    protected function typeLabel(): Attribute
    {
        return Attribute::make(get: fn () => $this->type_enum->label());
    }

    protected function accessEnum(): Attribute
    {
        return Attribute::make(
            get: fn () => ContentAccess::from($this->access),
        );
    }

    protected function accessLabel(): Attribute
    {
        return Attribute::make(get: fn () => $this->access_enum->label());
    }

    public function isBroadcast(): bool
    {
        return $this->type === PlaylistType::BROADCAST->value;
    }

    public function isClassic(): bool
    {
        return $this->type === PlaylistType::CLASSIC->value;
    }

    protected function firstVideoThumbnail(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $firstVideo = $this->videos()->published()->first();

                return $firstVideo?->thumbnail_urls['480'] ?? $firstVideo?->thumbnail_url;
            },
        );
    }
}
