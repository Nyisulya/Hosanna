<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;

class LocaleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $locale = Session::get('locale', config('app.locale', 'sw'));
        if (in_array($locale, ['en', 'sw'])) {
            App::setLocale($locale);
        } else {
            App::setLocale('sw');
        }
    }
}
