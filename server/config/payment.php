<?php

return [
    'gateways' => [
        'fake' => App\Services\Payment\Gateways\FakeGateway::class,
        'stripe' => [
            'class' => App\Services\Payment\Gateways\StripeGateway::class,
            'secret' => env('STRIPE_SECRET'),
            'public' => env('STRIPE_PUBLIC'),
        ],
    ],
];
