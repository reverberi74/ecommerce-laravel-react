<?php

namespace App\Services\Payment\Gateways;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Payment\DTOs\PaymentRequest;
use App\Services\Payment\DTOs\PaymentResponse;
use App\Services\Payment\Exceptions\PaymentException;
use App\Services\Payment\PaymentServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class StripeGateway implements PaymentServiceInterface
{
    protected StripeClient $client;

    public function __construct()
    {
        $this->client = new StripeClient(config('payment.gateways.stripe.secret'));
    }

    public function createPayment(PaymentRequest $request): PaymentResponse
    {
        $order = Order::findOrFail($request->orderId);

        try {
            $intent = $this->client->paymentIntents->create([
                'amount' => intval($order->total_amount * 100), // in centesimi
                'currency' => 'eur',
                'payment_method' => 'pm_card_visa', // ⚠️ metodo test fornito da Stripe
                'confirmation_method' => 'manual',
                'confirm' => false, // lo confermiamo dopo
                'metadata' => [
                    'order_id' => $order->id,
                    'user_id' => $order->user_id,
                ],
            ]);

            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method' => 'stripe',
                'gateway_transaction_id' => $intent->id,
                'amount' => $order->total_amount,
                'currency' => 'EUR',
                'status' => 'pending',
                'gateway_response' => $intent->toArray(),
            ]);

            return new PaymentResponse(
                success: true,
                transactionId: $intent->id,
                data: [
                    'payment_id' => $payment->id,
                    'client_secret' => $intent->client_secret,
                ]
            );
        } catch (ApiErrorException $e) {
            Log::error('Stripe createPayment failed', ['error' => $e->getMessage()]);

            return new PaymentResponse(
                success: false,
                error: $e->getMessage()
            );
        }
    }

    public function confirmPayment(string $paymentId): PaymentResponse
    {
        $payment = Payment::findOrFail($paymentId);

        try {
            $intent = $this->client->paymentIntents->retrieve($payment->gateway_transaction_id);
            $confirmedIntent = $this->client->paymentIntents->confirm($intent->id, [
                'return_url' => config('app.url') . '/payment/return',
            ]);
            $status = $confirmedIntent->status === 'succeeded' ? 'completed' : 'failed';

            $payment->update([
                'status' => $status,
                'paid_at' => $status === 'completed' ? now() : null,
                'gateway_response' => $confirmedIntent->toArray(),
            ]);

            return new PaymentResponse(
                success: $status === 'completed',
                transactionId: $intent->id,
                data: ['status' => $status],
                error: $status !== 'completed' ? 'Payment not completed' : null
            );
        } catch (ApiErrorException $e) {
            Log::error('Stripe confirmPayment failed', ['error' => $e->getMessage()]);

            return new PaymentResponse(
                success: false,
                error: $e->getMessage()
            );
        }
    }

    public function refundPayment(string $paymentId, float $amount): PaymentResponse
    {
        $payment = Payment::findOrFail($paymentId);

        try {
            $refund = $this->client->refunds->create([
                'payment_intent' => $payment->gateway_transaction_id,
                'amount' => intval($amount * 100),
            ]);

            return new PaymentResponse(
                success: true,
                transactionId: $refund->id,
                data: ['refunded_amount' => $amount]
            );
        } catch (ApiErrorException $e) {
            Log::error('Stripe refundPayment failed', ['error' => $e->getMessage()]);

            return new PaymentResponse(
                success: false,
                error: $e->getMessage()
            );
        }
    }

    public function savePaymentMethod(int $userId, array $data): array
    {
        // ⚠️ Da implementare nella FASE 11 (Stripe SetupIntent + tokenization client-side)
        throw new PaymentException('savePaymentMethod non ancora implementato con Stripe.');
    }

    public function getPaymentMethods(int $userId): Collection
    {
        // ⚠️ Da implementare nella FASE 11 (Stripe Customer → list payment methods)
        return collect();
    }
}
