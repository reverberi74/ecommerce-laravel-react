<?php

namespace App\Services\Payment\DTOs;

class PaymentRequest
{
    public function __construct(
        public int $orderId,
        public string $paymentMethod,
        public ?int $savedMethodId = null,
        public bool $saveMethod = false,
        public array $gatewayData = []
    ) {}
}
