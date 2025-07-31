<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RefundController extends Controller
{
    /**
     * DI del PaymentManager per risolvere il gateway corretto (stripe|paypal|fake).
     */
    public function __construct(private PaymentManager $payments)
    {
        //
    }

    /**
     * 🔄 Crea un rimborso per un pagamento esistente (admin).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_id' => 'required|exists:payments,id',
            'amount'     => 'required|numeric|min:0.01',
            'reason'     => 'nullable|string|max:255',
        ]);

        $payment = Payment::findOrFail($validated['payment_id']);

        // Consenti rimborsi solo su pagamenti completati
        if ($payment->status !== 'completed') {
            throw ValidationException::withMessages([
                'payment_id' => 'Il pagamento non è completato: rimborsi non consentiti.',
            ]);
        }

        // Importo massimo rimborsabile
        $refundedSum   = $payment->refunds()->sum('amount');
        $maxRefundable = max(0, $payment->amount - $refundedSum);

        if ($validated['amount'] > $maxRefundable) {
            throw ValidationException::withMessages([
                'amount' => 'Importo massimo rimborsabile: €' . number_format($maxRefundable, 2, ',', '.'),
            ]);
        }

        // 🔌 Seleziona il gateway in base al pagamento (stripe|paypal|fake)
        $gatewayCode = $payment->payment_method;
        $gateway     = $this->payments->gateway($gatewayCode);

        // ▶️ Esegui il refund sul gateway
        $response = $gateway->refundPayment(
            paymentId: (string) $payment->id,
            amount: (float) $validated['amount']
        );

        if (! $response->success) {
            return response()->json([
                'message' => 'Refund failed at gateway.',
                'error'   => $response->error ?? 'Unknown error',
            ], 422);
        }

        // 🧾 Persisti il refund locale con l’ID restituito dal gateway
        $refund = Refund::create([
            'payment_id'        => $payment->id,
            'amount'            => $validated['amount'],
            'gateway_refund_id' => $response->transactionId, // ← vero ID gateway
            'status'            => 'completed',
            'reason'            => $validated['reason'] ?? null,
            'refunded_by'       => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Rimborso effettuato.',
            'data'    => $refund,
        ]);
    }

    /**
     * 📂 Restituisce tutti i rimborsi legati a un pagamento.
     */
    public function index($paymentId)
    {
        $payment = Payment::with('refunds')->findOrFail($paymentId);

        return response()->json([
            'payment_id'     => $payment->id,
            'refunded_total' => $payment->refunds->sum('amount'),
            'refunds'        => $payment->refunds,
        ]);
    }
}
