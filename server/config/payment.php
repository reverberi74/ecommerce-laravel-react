<?php

return [
    'default' => env('PAYMENT_GATEWAY', 'stripe'),

    'user_agent' => env('PAYMENT_USER_AGENT', null),

    'gateways' => [
        'fake' => App\Services\Payment\Gateways\FakeGateway::class,

        'stripe' => [
            'class'  => App\Services\Payment\Gateways\StripeGateway::class,
            'secret' => env('STRIPE_SECRET'),
            'public' => env('STRIPE_PUBLIC'),
        ],

        'paypal' => [
            'class'         => App\Services\Payment\Gateways\PayPalGateway::class,
            'mode'          => env('PAYPAL_MODE', 'sandbox'), // sandbox|live
            'client_id'     => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_SECRET'), // oppure 'secret' => env('PAYPAL_SECRET')
            'webhook_id'    => env('PAYPAL_WEBHOOK_ID'),
            'base_url'      => env('PAYPAL_MODE', 'sandbox') === 'sandbox'
                                ? 'https://api-m.sandbox.paypal.com'
                                : 'https://api-m.paypal.com',
        ],
    ],
];

