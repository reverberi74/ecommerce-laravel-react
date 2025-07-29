<?php

namespace App\Services\Payment;

use App\Services\Payment\DTOs\PaymentRequest;
use App\Services\Payment\DTOs\PaymentResponse;
use Illuminate\Support\Collection;

interface PaymentServiceInterface
{
    public function createPayment(PaymentRequest $request): PaymentResponse;

    public function confirmPayment(string $paymentId): PaymentResponse;

    public function refundPayment(string $paymentId, float $amount): PaymentResponse;

    public function savePaymentMethod(int $userId, array $data): array;

    public function getPaymentMethods(int $userId): Collection;
}
