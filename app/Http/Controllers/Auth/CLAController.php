<?php

namespace App\Http\Controllers\Auth;

use App\Auth\CLAProvider;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CLAController extends Controller
{
    public function __construct(private CLAProvider $cla) {}

    public function login(): SymfonyResponse
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }

        // CLA is on another domain: a plain redirect fails CORS on Inertia visits.
        return Inertia::location($this->cla->redirect()->getTargetUrl());
    }

    public function handleCallback(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }

        try {
            $claim = $this->cla->user()->getRaw();
        } catch (\Throwable) {
            abort(500, 'Authentification CLA échouée');
        }

        $user = User::firstOrNew(['username' => $claim['sub']]);

        if (! $user->exists) {
            $user->first_name = $claim['given_name'];
            $user->last_name = $claim['family_name'];
            $user->alumni = false;
        }

        $user->school_email = $claim['email'];
        $user->promo = $claim['promo'];
        $user->logged_on = now();
        $user->save();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function logout(Request $request): SymfonyResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Inertia::location($this->cla->logoutUrl());
    }
}
