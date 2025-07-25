<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class CheckoutOrderController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();

        // Recupera carrello utente con i prodotti
        $cart = Cart::with('items.product')->where('user_id', $user->id)->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        DB::beginTransaction();

        try {
            // Calcolo totale
            $total = $cart->items->sum(fn($item) => $item->quantity * $item->product->price);

            // Crea ordine
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => strtoupper(Str::random(10)),
                'total_amount' => $total,
                'status' => 'pending',
            ]);

            // Crea item ordine
            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product->id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                    'subtotal' => $item->quantity * $item->product->price,
                    'product_snapshot' => json_encode($item->product),
                ]);
            }

            // Svuota carrello
            $cart->items()->delete();
            $cart->delete();

            DB::commit();

            return response()->json(['message' => 'Order created successfully', 'order_id' => $order->id], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error during checkout', 'error' => $e->getMessage()], 500);
        }
    }
}
