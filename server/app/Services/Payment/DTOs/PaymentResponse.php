<?php

namespace App\Services\Payment\DTOs;

class PaymentResponse
{
    public function __construct(
        public bool $success,
        public ?string $transactionId = null,
        public ?array $data = null,
        public ?string $error = null
    ) {}
}
