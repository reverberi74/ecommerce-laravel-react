<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentMethodController extends Controller
{
    /**
     * 📋 Restituisce tutti i metodi di pagamento salvati dell'utente loggato.
     */
    public function index()
    {
        $methods = PaymentMethod::where('user_id', Auth::id())->get();

        return response()->json([
            'data' => $methods
        ]);
    }

    /**
     * ➕ Salva un nuovo metodo di pagamento (es. carta, PayPal...).
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:card,paypal',
            'gateway' => 'required|string|in:stripe,paypal,fake',
            'gateway_method_id' => 'required|string',
            'metadata' => 'nullable|array',
            'is_default' => 'boolean',
        ]);

        // Se è predefinito, annulla gli altri
        if ($request->boolean('is_default')) {
            PaymentMethod::where('user_id', Auth::id())
                ->where('gateway', $request->input('gateway'))
                ->update(['is_default' => false]);
        }

        $method = PaymentMethod::create([
            'user_id' => Auth::id(),
            'type' => $request->input('type'),
            'gateway' => $request->input('gateway'),
            'gateway_method_id' => $request->input('gateway_method_id'),
            'metadata' => $request->input('metadata', []),
            'is_default' => $request->boolean('is_default'),
        ]);

        return response()->json([
            'message' => 'Metodo salvato correttamente.',
            'data' => $method
        ]);
    }

    /**
     * ❌ Elimina un metodo salvato.
     */
    public function destroy($id)
    {
        $method = PaymentMethod::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $method->delete();

        return response()->json([
            'message' => 'Metodo eliminato.'
        ]);
    }

    /**
     * ⭐ Imposta il metodo come predefinito.
     */
    public function setDefault($id)
    {
        $method = PaymentMethod::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Reset degli altri metodi
        PaymentMethod::where('user_id', Auth::id())
            ->where('gateway', $method->gateway)
            ->update(['is_default' => false]);

        $method->is_default = true;
        $method->save();

        return response()->json([
            'message' => 'Metodo impostato come predefinito.',
            'data' => $method
        ]);
    }
}

