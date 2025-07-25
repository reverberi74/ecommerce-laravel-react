<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'user_id' => $this->user_id,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'created_at' => $this->created_at->toDateTimeString(),

            'items' => OrderItemResource::collection($this->whenLoaded('items')),

            // Facoltativo: lo includeremo nel controller con ->load('statusHistories')
            'status_history' => $this->whenLoaded('statusHistories', function () {
                return $this->statusHistories->map(function ($history) {
                    return [
                        'old_status' => $history->old_status,
                        'new_status' => $history->new_status,
                        'changed_by' => $history->changed_by,
                        'notes' => $history->notes,
                        'created_at' => $history->created_at->toDateTimeString(),
                    ];
                });
            }),
        ];
    }
}
