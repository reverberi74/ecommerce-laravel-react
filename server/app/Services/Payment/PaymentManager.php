<?php

namespace App\Services\Payment;

use App\Services\Payment\Gateways\StripeGateway;
use App\Services\Payment\Gateways\PayPalGateway;
use App\Services\Payment\Gateways\FakeGateway;
use InvalidArgumentException;

class PaymentManager
{
    public function gateway(string $gateway): PaymentServiceInterface
    {
        return match($gateway) {
            'stripe' => app(StripeGateway::class),
            'paypal' => app(PayPalGateway::class),
            'fake'   => app(FakeGateway::class),
            default  => throw new InvalidArgumentException("Gateway [$gateway] non supportato."),
        };
    }
}
