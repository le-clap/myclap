<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    protected $table = 'category';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'slug',
        'label',
        'description',
        'created_by',
        'created_on',
    ];

    protected function casts(): array
    {
        return [
            'created_on' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(
            Video::class,
            'video_category',
            'category_slug',
            'video_token',
            'slug',
            'token'
        );
    }

    public function publishedVideos(?User $user = null): BelongsToMany
    {
        return $this->videos()->published()->accessibleBy($user);
    }
}
