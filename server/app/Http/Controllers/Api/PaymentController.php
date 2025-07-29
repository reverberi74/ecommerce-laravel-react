<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InitiatePaymentRequest;
use App\Http\Requests\ConfirmPaymentRequest;
use App\Services\Payment\DTOs\PaymentRequest;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(protected PaymentManager $manager) {}

    /**
     * Avvia un nuovo pagamento.
     */
    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $dto = new PaymentRequest(
            orderId: $request->input('order_id'),
            paymentMethod: $request->input('payment_method'),
            savedMethodId: $request->input('saved_method_id'),
            saveMethod: $request->boolean('save_method'),
            gatewayData: $request->input('gateway_data', [])
        );

        $gateway = $this->manager->gateway($dto->paymentMethod);
        $response = $gateway->createPayment($dto);

        return response()->json($response);
    }

    /**
     * Conferma il pagamento dopo la fase client-side.
     */
    public function confirm(ConfirmPaymentRequest $request): JsonResponse
    {
        $gateway = $this->manager->gateway($request->input('payment_method'));
        $response = $gateway->confirmPayment($request->input('payment_id'));

        return response()->json($response);
    }

    /**
     * Restituisce lo stato di un pagamento.
     */
    public function status(string $paymentId): JsonResponse
    {
        $payment = \App\Models\Payment::findOrFail($paymentId);

        return response()->json([
            'status' => $payment->status,
            'paid_at' => $payment->paid_at,
            'gateway' => $payment->payment_method,
        ]);
    }
}
