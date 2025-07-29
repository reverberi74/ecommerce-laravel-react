<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_method',
        'gateway_transaction_id',
        'amount',
        'currency',
        'status',
        'gateway_response',
        'paid_at',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'paid_at' => 'datetime',
    ];

    // 🔁 Relazione con ordine
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // 🔁 Eventuali rimborsi legati
    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }
}
