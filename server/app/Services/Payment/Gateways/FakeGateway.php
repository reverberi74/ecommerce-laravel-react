<?php

namespace App\Services\Payment\Gateways;

use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\Payment\DTOs\PaymentRequest;
use App\Services\Payment\DTOs\PaymentResponse;
use App\Services\Payment\Exceptions\PaymentException;
use App\Services\Payment\PaymentServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FakeGateway implements PaymentServiceInterface
{
    public function createPayment(PaymentRequest $request): PaymentResponse
    {
        $transactionId = 'FAKE_' . strtoupper(Str::random(12));
        $success = $this->simulateSuccess();

        // 🔍 Recupera l'importo reale dell'ordine
        $order = \App\Models\Order::findOrFail($request->orderId);

        // 💾 Crea il pagamento associato
        $payment = Payment::create([
            'order_id' => $request->orderId,
            'payment_method' => 'fake',
            'gateway_transaction_id' => $success ? $transactionId : null,
            'amount' => $order->total_amount, // ✅ Ora dinamico
            'currency' => 'EUR',
            'status' => $success ? 'pending' : 'failed',
            'gateway_response' => [
                'simulated' => true,
                'gateway' => 'fake',
                'success' => $success,
            ],
        ]);

        return new PaymentResponse(
            success: $success,
            transactionId: $success ? $transactionId : null,
            data: ['payment_id' => $payment->id],
            error: $success ? null : 'Simulated failure during creation'
        );
    }


    public function confirmPayment(string $paymentId): PaymentResponse
    {
        $payment = Payment::findOrFail($paymentId);

        $success = $this->simulateSuccess();
        $payment->update([
            'status' => $success ? 'completed' : 'failed',
            'paid_at' => $success ? now() : null,
            'gateway_response' => array_merge($payment->gateway_response ?? [], [
                'confirmed' => true,
                'success' => $success,
            ]),
        ]);

        return new PaymentResponse(
            success: $success,
            transactionId: $payment->gateway_transaction_id,
            data: ['status' => $payment->status],
            error: $success ? null : 'Simulated failure during confirmation'
        );
    }

    public function refundPayment(string $paymentId, float $amount): PaymentResponse
    {
        $payment = Payment::findOrFail($paymentId);

        if ($amount > $payment->amount) {
            throw new PaymentException("Importo rimborsato superiore all'importo originale.");
        }

        $success = $this->simulateSuccess();

        // Logica fittizia per il rimborso
        if ($success) {
            $payment->status = 'refunded';
            $payment->save();
        }

        return new PaymentResponse(
            success: $success,
            transactionId: $payment->gateway_transaction_id,
            data: ['refunded_amount' => $amount],
            error: $success ? null : 'Simulated refund failure'
        );
    }

    public function savePaymentMethod(int $userId, array $data): array
    {
        $method = PaymentMethod::create([
            'user_id' => $userId,
            'type' => $data['type'] ?? 'card',
            'gateway' => 'fake',
            'gateway_method_id' => 'FAKE_METHOD_' . Str::random(10),
            'metadata' => $data['metadata'] ?? [],
            'is_default' => $data['is_default'] ?? false,
        ]);

        return $method->toArray();
    }

    public function getPaymentMethods(int $userId): Collection
    {
        return PaymentMethod::where('user_id', $userId)
            ->where('gateway', 'fake')
            ->get();
    }

    private function simulateSuccess(): bool
    {
        // Prende dal config la probabilità di successo
        $rate = config('payment.gateways.fake.success_rate', 0.9);
        return mt_rand() / mt_getrandmax() <= $rate;
    }
}
