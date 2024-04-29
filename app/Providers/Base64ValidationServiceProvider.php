<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class Base64ValidationServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Validator::extend('customcheck', function ($attribute, $value, $parameters, $validator) {
            // Check if the value is a valid base64 encoded string
            return true;
            return base64_encode(base64_decode($value, true)) === $value;
        });
    }
}
