<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RefundController extends Controller
{
    /**
     * 🔄 Crea un rimborso per un pagamento esistente (admin).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_id' => 'required|exists:payments,id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:255',
        ]);

        $payment = Payment::findOrFail($validated['payment_id']);

        // ⚠️ Verifica importo massimo rimborsabile
        $refundedSum = $payment->refunds()->sum('amount');
        $maxRefundable = $payment->amount - $refundedSum;

        if ($validated['amount'] > $maxRefundable) {
            throw ValidationException::withMessages([
                'amount' => "Importo massimo rimborsabile: €" . number_format($maxRefundable, 2),
            ]);
        }

        // Crea il rimborso (simulato, solo lato DB)
        $refund = Refund::create([
            'payment_id' => $payment->id,
            'amount' => $validated['amount'],
            'gateway_refund_id' => 'SIMULATED_REFUND_' . strtoupper(uniqid()),
            'status' => 'completed',
            'reason' => $validated['reason'],
            'refunded_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Rimborso effettuato.',
            'data' => $refund,
        ]);
    }

    /**
     * 📂 Restituisce tutti i rimborsi legati a un pagamento.
     */
    public function index($paymentId)
    {
        $payment = Payment::with('refunds')->findOrFail($paymentId);

        return response()->json([
            'payment_id' => $payment->id,
            'refunded_total' => $payment->refunds->sum('amount'),
            'refunds' => $payment->refunds,
        ]);
    }
}

