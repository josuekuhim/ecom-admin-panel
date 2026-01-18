<?php

namespace App\Providers;

use App\Contracts\ClerkAuthenticator;
use App\Contracts\ClerkClient;
use App\Contracts\CustomerLoginHandler;
use App\Contracts\CustomerProvisioner;
use App\Contracts\PaymentGateway;
use App\Contracts\ShippingGateway;
use App\Services\ClerkAuthenticationService;
use App\Services\ClerkCustomerProvisioningService;
use App\Services\ClerkService;
use App\Services\CustomerLoginService;
use App\Services\InfinitePayService;
use App\Services\ShippingService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // External API clients
        $this->app->bind(ClerkClient::class, ClerkService::class);
        $this->app->bind(PaymentGateway::class, InfinitePayService::class);
        $this->app->bind(ShippingGateway::class, ShippingService::class);

        // Authentication services
        $this->app->bind(ClerkAuthenticator::class, ClerkAuthenticationService::class);
        $this->app->bind(CustomerProvisioner::class, ClerkCustomerProvisioningService::class);
        $this->app->bind(CustomerLoginHandler::class, CustomerLoginService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS in production to fix mixed content issues
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
