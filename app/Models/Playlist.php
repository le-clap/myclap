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
            'type' => PlaylistType::class,
            'access' => ContentAccess::class,
            'position' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
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
        return $this->videos()
            ->published()
            ->accessibleBy($user, true)
            ->get();
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

    protected function typeLabel(): Attribute
    {
        return Attribute::make(get: fn () => $this->type->label());
    }

    protected function accessLabel(): Attribute
    {
        return Attribute::make(get: fn () => $this->access->label());
    }

    public function isBroadcast(): bool
    {
        return $this->type === PlaylistType::BROADCAST;
    }

    public function isClassic(): bool
    {
        return $this->type === PlaylistType::CLASSIC;
    }
}
