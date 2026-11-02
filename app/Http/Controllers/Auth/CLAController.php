<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\CLAAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CLAController extends Controller
{
    public function __construct(
        private CLAAuthService $claAuth
    ) {}

    public function login(): SymfonyResponse
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }

        // CLA is on another domain: a plain redirect fails CORS on Inertia visits.
        return Inertia::location($this->claAuth->getAuthUrl());
    }

    public function handleCallback(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended('/');
        }

        $ticket = $request->get('ticket');

        if (! $ticket) {
            abort(400, 'Ticket manquant');
        }

        $claData = $this->claAuth->validateTicket($ticket);

        if (! $claData) {
            abort(500, 'Authentification CLA échouée');
        }

        $user = $this->claAuth->createOrUpdateUser($claData);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
