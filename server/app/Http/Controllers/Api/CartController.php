<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\CartResource;

class CartController extends Controller
{
    // ✅ Recupera o crea il carrello attivo (user o guest)
    protected function getActiveCart(Request $request): Cart
    {
        if (Auth::check()) {
            return Cart::firstOrCreate(['user_id' => Auth::id()]);
        }

        $sessionId = $request->session()->getId();
        return Cart::firstOrCreate(['session_id' => $sessionId]);
    }

    // ✅ GET /api/cart → Visualizza carrello
    public function getCart(Request $request)
    {
        $cart = $this->getActiveCart($request)->load(['items.product']);

        return response()->json([
            'cart' => new CartResource($cart)
        ]);
    }

    // ✅ POST /api/cart/add
    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = $this->getActiveCart($request);
        $product = Product::findOrFail($request->product_id);

        // ✅ STOCK disponibile?
        if ($product->stock <= 0) {
            return response()->json([
                'error' => 'Prodotto attualmente non disponibile a magazzino.'
            ], 400);
        }

        // ✅ Quantità richiesta maggiore dello stock?
        if ($request->quantity > $product->stock) {
            return response()->json([
                'error' => "Quantità richiesta superiore allo stock disponibile ({$product->stock})."
            ], 400);
        }

        // ✅ Quantità massima per singolo prodotto (business rule)
        $MAX_PER_PRODUCT = 10;
        if ($request->quantity > $MAX_PER_PRODUCT) {
            return response()->json([
                'error' => "Puoi aggiungere al massimo {$MAX_PER_PRODUCT} unità per questo prodotto."
            ], 400);
        }

        // ✅ Gestione nuova o esistente
        $item = $cart->items()->firstOrNew(['product_id' => $product->id]);

        // Calcola quantità totale finale (es: già ne avevo 2, aggiungo altri 3)
        $newTotalQty = $item->quantity + $request->quantity;

        if ($newTotalQty > $product->stock) {
            return response()->json([
                'error' => "Quantità totale nel carrello supererebbe lo stock disponibile ({$product->stock})."
            ], 400);
        }

        if ($newTotalQty > $MAX_PER_PRODUCT) {
            return response()->json([
                'error' => "Puoi avere al massimo {$MAX_PER_PRODUCT} unità nel carrello per questo prodotto."
            ], 400);
        }

        $item->quantity = $newTotalQty;
        $item->price = $product->price; // snapshot al momento
        $item->save();

        return $this->getCart($request);
    }


    // ✅ PUT /api/cart/item/{id}
    public function updateItem(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $item = CartItem::findOrFail($id);
        $product = $item->product;

        // ✅ Controlla disponibilità stock
        if ($product->stock <= 0) {
            return response()->json([
                'error' => 'Prodotto esaurito. Non puoi aggiornare la quantità.'
            ], 400);
        }

        if ($request->quantity > $product->stock) {
            return response()->json([
                'error' => "Stock insufficiente. Disponibili solo {$product->stock} unità."
            ], 400);
        }

        // ✅ Limite massimo per prodotto (regola business)
        $MAX_PER_PRODUCT = 10;
        if ($request->quantity > $MAX_PER_PRODUCT) {
            return response()->json([
                'error' => "Puoi avere al massimo {$MAX_PER_PRODUCT} unità nel carrello per questo prodotto."
            ], 400);
        }

        // ✅ Aggiorna quantità
        $item->quantity = $request->quantity;
        $item->save();

        return $this->getCart($request);
    }


    // ✅ DELETE /api/cart/item/{id}
    public function removeItem(Request $request, $id)
    {
        $item = CartItem::findOrFail($id);
        $item->delete();

        return $this->getCart($request);
    }

    // ✅ DELETE /api/cart/clear
    public function clearCart(Request $request)
    {
        $cart = $this->getActiveCart($request);
        $cart->items()->delete();

        return $this->getCart($request);
    }

    // ✅ POST /api/cart/merge
    public function mergeGuestCart(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'User not authenticated'], 403);
        }

        $sessionId = $request->session()->getId();
        $guestCart = Cart::where('session_id', $sessionId)->first();
        $userCart = Cart::firstOrCreate(['user_id' => Auth::id()]);

        if (!$guestCart || $guestCart->id === $userCart->id) {
            return $this->getCart($request); // niente da fondere
        }

        foreach ($guestCart->items as $guestItem) {
            $existing = $userCart->items()->where('product_id', $guestItem->product_id)->first();

            if ($existing) {
                $existing->quantity += $guestItem->quantity;
                $existing->save();
            } else {
                $guestItem->cart_id = $userCart->id;
                $guestItem->save();
            }
        }

        $guestCart->delete();

        return $this->getCart($request);
    }
}
