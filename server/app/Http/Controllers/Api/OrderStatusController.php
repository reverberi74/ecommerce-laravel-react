<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class OrderStatusController extends Controller
{
    /**
     * Aggiorna lo stato di un ordine e registra la cronologia
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        // ✅ Solo admin può aggiornare
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        // ✅ Validazione input
        $validated = $request->validate([
            'new_status' => [
                'required',
                Rule::in(['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])
            ],
            'notes' => 'nullable|string|max:1000'
        ]);

        // ✅ Recupero ordine
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        $oldStatus = $order->status;
        $newStatus = $validated['new_status'];

        // ⚠️ Evita update inutile
        if ($oldStatus === $newStatus) {
            return response()->json(['message' => 'Status is already ' . $newStatus], Response::HTTP_NOT_MODIFIED);
        }

        try {
            // ✅ Update ordine
            $order->status = $newStatus;
            $order->save();

            // ✅ Log nella cronologia
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_by' => $user->id,
                'notes' => $validated['notes'] ?? null,
            ]);

            return response()->json(['message' => 'Order status updated successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating order status',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
