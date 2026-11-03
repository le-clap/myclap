<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginRedirectTest extends TestCase
{
    public function test_login_redirects_to_cla_for_plain_requests(): void
    {
        $this->get('/login')->assertRedirectContains('/authentification/');
    }

    public function test_inertia_visits_get_a_full_page_redirect_to_cla(): void
    {
        // A plain 302 to another domain would fail CORS inside Inertia's XHR.
        $this->get('/login', ['X-Inertia' => 'true'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location');
    }
}
