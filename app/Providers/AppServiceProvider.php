<?php

namespace App\Providers;

use App\Services\Payment\Contracts\PaymentProvider;
use App\Services\Payment\Providers\FedaPayPaymentProvider;
use App\Services\Payment\Providers\SandboxPaymentProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentProvider::class, function ($app) {
            $isProduction = $app->environment('production');
            $isSandbox = config('payment.sandbox');

            if ($isProduction && ! $isSandbox) {
                return new FedaPayPaymentProvider();
            }

            return new SandboxPaymentProvider();
        });
    }

    public function boot(): void
    {
        //
    }
}
