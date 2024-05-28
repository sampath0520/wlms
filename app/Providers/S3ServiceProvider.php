<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Aws\S3\S3Client;

class S3ServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind('s3', function () {
            return new S3Client([
                'version' => 'latest',
                'region' => config('services.s3.region'),
                'credentials' => [
                    'key' => config('services.s3.key'),
                    'secret' => config('services.s3.secret'),
                ],
            ]);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
