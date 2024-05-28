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
        // Set the default timezone for the entire application
        // date_default_timezone_set(config('app.timezone'));

        Validator::extendImplicit('custom_validation_response', function ($attribute, $value, $parameters, $validator) {
            $messages = $validator->errors()->getMessages();

            $customResponse = [
                'status_code' => 0,
                'status' => false,
                'message' => 'Validation Error',
                'data' => []
            ];

            throw \Illuminate\Validation\ValidationException::withMessages(['errors' => $customResponse]);
        });
    }
}
