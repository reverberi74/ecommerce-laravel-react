<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'image' => $this->product->image,
                'current_price' => $this->product->price,
            ],
            'quantity' => $this->quantity,
            'price' => $this->price, // snapshot al momento dell’aggiunta
            'subtotal' => $this->subtotal,
            'price_changed' => $this->price_changed,
        ];
    }
}
