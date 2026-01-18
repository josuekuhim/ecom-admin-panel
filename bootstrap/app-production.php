<?php

use Illuminate\Foundation\Application;

$app = new Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

// Skip dev-only service providers in production
if ($app->environment('production')) {
    $app->booted(function ($app) {
        // Remove dev-only providers that might be cached
        $devProviders = [
            'Laravel\Pail\PailServiceProvider',
            'Laravel\Sail\SailServiceProvider',
        ];
        
        foreach ($devProviders as $provider) {
            if ($app->getProvider($provider)) {
                $app->getProvider($provider)->register();
            }
        }
    });
}

return $app;