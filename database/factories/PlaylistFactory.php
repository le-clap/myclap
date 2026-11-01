<?php

namespace Database\Factories;

use App\Enums\ContentAccess;
use App\Models\Playlist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Playlist> */
class PlaylistFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(2),
            'slug' => Str::slug(fake()->unique()->words(3, true)),
            'access' => ContentAccess::PUBLIC,
            'modified_by' => fn () => User::factory()->create()->username,
        ];
    }
}
