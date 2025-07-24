<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CartItemResource;

class CartResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'items_count' => $this->items_count,
            'total_amount' => $this->total_amount,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
        ];
    }
}


