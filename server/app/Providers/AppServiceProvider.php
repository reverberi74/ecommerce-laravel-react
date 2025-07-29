<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use App\Services\Payment\Gateways\StripeGateway;
use App\Services\Payment\Gateways\FakeGateway;
use App\Services\Payment\Gateways\PayPalGateway;
use App\Services\Payment\PaymentManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ImageManager::class, function () {
            return extension_loaded('imagick')
                ? new ImageManager(new ImagickDriver())
                : new ImageManager(new GdDriver());
        });

        // 💳 Registrazione Payment Gateways
        $this->app->singleton(StripeGateway::class);
        $this->app->singleton(FakeGateway::class);
        $this->app->singleton(PayPalGateway::class);

        // 📦 Registrazione Payment Manager
        $this->app->singleton(PaymentManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
