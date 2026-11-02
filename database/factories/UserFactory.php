<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'promo' => 2027,
            'logged_on' => now(),
        ];
    }

    public function withPermissions(string ...$identifiers): static
    {
        return $this->afterCreating(function (User $user) use ($identifiers) {
            foreach ($identifiers as $identifier) {
                UserPermission::create([
                    'username' => $user->username,
                    'identifier' => $identifier,
                    'created_by' => $user->username,
                ]);
            }
        });
    }
}
