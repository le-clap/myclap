<?php

namespace App\Models;

use App\Enums\ContentAccess;
use App\Enums\UploadStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Video extends Model
{
    protected $table = 'video';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'token',
        'name',
        'description',
        'thumbnail_identifier',
        'file_identifier',
        'access',
        'upload_status',
        'views',
        'reactions',
        'duration',
        'file_size',
        'created_on',
        'uploaded_on',
        'uploaded_by',
        'video_codec',
        'audio_codec',
        'width',
        'height',
        'bitrate',
        'faststart',
    ];

    protected $appends = [
        'thumbnail_url',
        'thumbnail_urls',
        'video_url',
        'author',
        'access_label',
        'upload_status_label',
    ];

    protected function casts(): array
    {
        return [
            'access' => ContentAccess::class,
            'views' => 'integer',
            'reactions' => 'integer',
            'duration' => 'integer',
            'file_size' => 'integer',
            'created_on' => 'date',
            'uploaded_on' => 'datetime',
            'upload_status' => UploadStatus::class,
            'width' => 'integer',
            'height' => 'integer',
            'bitrate' => 'integer',
            'faststart' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function isPublished(): bool
    {
        return $this->upload_status === UploadStatus::UPLOAD_END;
    }

    // Relations
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'username');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'video_category',
            'video_token',
            'category_slug',
            'token',
            'slug'
        );
    }

    public function playlists(): BelongsToMany
    {
        return $this->belongsToMany(
            Playlist::class,
            'playlist_video',
            'video_token',
            'playlist_slug',
            'token',
            'slug'
        )->withPivot('position');
    }

    public function videoReactions(): HasMany
    {
        return $this->hasMany(VideoReaction::class, 'video_token', 'token');
    }

    public function videoViews(): HasMany
    {
        return $this->hasMany(VideoView::class, 'video_token', 'token');
    }

    public function upload(): HasOne
    {
        return $this->hasOne(VideoUpload::class, 'video_token', 'token');
    }

    public function transcodeAttempts(): HasMany
    {
        return $this->hasMany(VideoTranscode::class, 'video_token', 'token');
    }

    public function latestTranscodeAttempt(): HasOne
    {
        return $this->hasOne(VideoTranscode::class, 'video_token', 'token')
            ->latestOfMany(['started_on', 'id']);
    }

    // Scopes
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('upload_status', UploadStatus::UPLOAD_END->value);
    }

    public function scopeAccessibleBy(Builder $query, ?User $user, bool $includeUnlisted = false): Builder
    {
        $accesses = $user
            ? [ContentAccess::CENTRALIENS, ContentAccess::PUBLIC]
            : [ContentAccess::PUBLIC];

        if ($includeUnlisted) {
            $accesses[] = ContentAccess::UNLINKED;
        }

        return $query->whereIn('access', array_map(fn (ContentAccess $a) => $a->value, $accesses));
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(
            fn (Builder $q) => $q->where('name', 'ILIKE', "%{$term}%")
                ->orWhere('token', 'ILIKE', "%{$term}%")
        );
    }

    // Accessors
    protected function author(): Attribute
    {
        return Attribute::make(get: fn () => $this->uploaded_by);
    }

    protected function thumbnailUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => route('watch.media.thumbnail', ['video' => $this->token, 'size' => 1080])
                .$this->versionQuery($this->thumbnail_identifier)
        );
    }

    protected function thumbnailUrls(): Attribute
    {
        $version = $this->versionQuery($this->thumbnail_identifier);

        return Attribute::make(get: fn () => [
            '1080' => route('watch.media.thumbnail', ['video' => $this->token, 'size' => 1080]).$version,
            '480' => route('watch.media.thumbnail', ['video' => $this->token, 'size' => 480]).$version,
            '120' => route('watch.media.thumbnail', ['video' => $this->token, 'size' => 120]).$version,
        ]);
    }

    protected function videoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => route('watch.media.video', ['video' => $this->token])
                .$this->versionQuery($this->file_identifier)
        );
    }

    /**
     * Appending a fingerprint of the current file identifier
     * makes the URL itself change whenever the content does, forcing a fresh fetch.
     */
    private function versionQuery(?string $identifier): string
    {
        if ($identifier === null) {
            return '';
        }

        return '?v='.self::version($identifier);
    }

    public static function version(string $identifier): string
    {
        return substr(sha1($identifier), 0, 8);
    }

    protected function accessLabel(): Attribute
    {
        return Attribute::make(get: fn () => $this->access->label());
    }

    protected function uploadStatusLabel(): Attribute
    {
        return Attribute::make(get: fn () => $this->upload_status->label());
    }

    public function syncCategories(array $categorySlugs): void
    {
        $validSlugs = Category::whereIn('slug', $categorySlugs)->pluck('slug')->toArray();
        $this->categories()->sync($validSlugs);
    }
}
