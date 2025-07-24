<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\CartItem;
use App\Models\Cart;

class CheckCart
{
    public function handle(Request $request, Closure $next): Response
    {
        // Recupera l'item dal parametro URL (es. /item/{id})
        $itemId = $request->route('id');
        $item = CartItem::find($itemId);

        if (!$item) {
            return response()->json(['error' => 'Articolo del carrello non trovato'], 404);
        }

        // Recupera il carrello corrente (user o session)
        $cartQuery = Cart::query();

        if (Auth::check()) {
            $cartQuery->where('user_id', Auth::id());
        } else {
            $cartQuery->where('session_id', $request->session()->getId());
        }

        $cart = $cartQuery->first();

        if (!$cart || $item->cart_id !== $cart->id) {
            return response()->json(['error' => 'Non hai accesso a questo articolo del carrello'], 403);
        }

        return $next($request);
    }
}
