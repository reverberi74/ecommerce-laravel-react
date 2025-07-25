<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
        'subtotal',
        'product_snapshot',
    ];

    protected $casts = [
        'price' => 'float',
        'subtotal' => 'float',
        'product_snapshot' => 'array', // ✅ snapshot JSON
    ];

    // 🔗 Relazioni

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed(); // per compatibilità soft delete
    }
}
