<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

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
        Validator::extend('base64', function ($attribute, $value, $parameters, $validator) {
            $file = base64_decode($value, true);
            return base64_encode(base64_decode($value, true)) === $value;
        });

        Validator::extend('email_strict', function ($attribute, $value, $parameters, $validator) {
            return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        });
    }
}
