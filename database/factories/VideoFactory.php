<?php

namespace Database\Factories;

use App\Enums\ContentAccess;
use App\Enums\UploadStatus;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Video> */
class VideoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'token' => Str::random(6),
            'created_on' => now()->toDateString(),
            'access' => ContentAccess::PUBLIC,
            'upload_status' => UploadStatus::UPLOAD_END,
            'uploaded_by' => fn () => User::factory()->create()->username,
        ];
    }

    public function access(ContentAccess $access): static
    {
        return $this->state(['access' => $access]);
    }
}
