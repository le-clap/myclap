<?php

namespace App\Providers;

use App\Auth\CLAProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Socialite::extend('cla', fn () => Socialite::buildProvider(CLAProvider::class, config('services.cla')));

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
