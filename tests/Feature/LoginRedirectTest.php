<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginRedirectTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.cla.client_id' => 'id', 'services.cla.issuer' => 'https://cla.test/auth']);
    }

    public function test_login_redirects_to_cla_for_plain_requests(): void
    {
        $this->get('/login')->assertRedirectContains('/auth/authorize/');
    }

    public function test_inertia_visits_get_a_full_page_redirect_to_cla(): void
    {
        // A plain 302 to another domain would fail CORS inside Inertia's XHR.
        $this->get('/login', ['X-Inertia' => 'true'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location');
    }

    public function test_logout_ends_the_session_then_goes_to_cla_logout_with_client_id(): void
    {
        $this->post('/logout', [], ['X-Inertia' => 'true'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', 'https://cla.test/auth/logout/?client_id=id&post_logout_redirect_uri='.urlencode(url('/')));
    }
}
