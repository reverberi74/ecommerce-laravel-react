<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    /**
     * Mostra tutti gli ordini dell’utente autenticato
     */
    public function index()
    {
        $user = Auth::user();

        $orders = Order::with(['items', 'statusHistories'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return OrderResource::collection($orders);
    }

    /**
     * Mostra un singolo ordine dell’utente (con controlli di autorizzazione)
     */
    public function show($id)
    {
        $user = Auth::user();

        $order = Order::with(['items', 'statusHistories'])
            ->where('user_id', $user->id)
            ->find($id);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found or access denied',
            ], Response::HTTP_NOT_FOUND);
        }

        return new OrderResource($order);
    }
}
