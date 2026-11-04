<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class RememberMeTest extends TestCase
{
    use RefreshDatabase;

    private function guard(): SessionGuard
    {
        /** @var SessionGuard */
        return Auth::guard('web');
    }

    /** A visit with an expired session: only the remember cookie is left. */
    private function visitWithRememberCookie(string $value): TestResponse
    {
        $name = $this->guard()->getRecallerName();
        Auth::forgetGuards();
        $this->app['session.store']->flush();

        return $this->withCookie($name, $value)->get('/login');
    }

    private function issuedCookie(User $user): string
    {
        $this->guard()->login($user, remember: true);

        return $this->guard()->getCookieJar()->queued($this->guard()->getRecallerName())->getValue();
    }

    public function test_remember_cookie_logs_the_user_back_in_after_the_session_expires(): void
    {
        $user = User::factory()->create();

        $this->visitWithRememberCookie($this->issuedCookie($user))->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_cookie_issued_before_get_auth_password_returned_a_string_still_works(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['remember_token' => 'token'])->save();
        $hash = $this->guard()->hashPasswordForCookie(null);

        $this->visitWithRememberCookie("{$user->id}|token|{$hash}")->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_remember_cookie_with_a_wrong_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['remember_token' => 'token'])->save();
        $hash = $this->guard()->hashPasswordForCookie(null);

        $this->visitWithRememberCookie("{$user->id}|wrong|{$hash}");

        $this->assertGuest();
    }
}
