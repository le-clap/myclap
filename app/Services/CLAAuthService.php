<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class CLAAuthService
{
    public function getAuthUrl(): string
    {
        $host = config('services.cla.host');
        $identifier = config('services.cla.identifier');

        return "{$host}/authentification/{$identifier}";
    }

    public function validateTicket(string $ticket): ?array
    {
        $host = config('services.cla.host');
        $identifier = config('services.cla.identifier');
        $url = "{$host}/authentification/{$identifier}/".urlencode($ticket);

        try {
            $response = Http::get($url);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            return $data['success'] ? $data['payload'] : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function createOrUpdateUser(array $claData): User
    {
        $user = User::firstOrNew(['username' => $claData['username']]);

        if (! $user->exists) {
            $user->first_name = $claData['firstName'];
            $user->last_name = $claData['lastName'];
            $user->alumni = false;
        }

        $user->school_email = $claData['emailSchool'];
        $user->promo = $claData['promo'];
        $user->logged_on = now();
        $user->save();

        return $user;
    }
}
