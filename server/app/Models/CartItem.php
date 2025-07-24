<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'price',
    ];

    // 🔁 Relazione con Cart
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    // 🔁 Relazione con Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // 📦 Subtotale (prezzo * quantità)
    public function getSubtotalAttribute()
    {
        return $this->quantity * $this->price;
    }

    // 📌 Indica se il prezzo è cambiato (rispetto a quello attuale del prodotto)
    public function getPriceChangedAttribute()
    {
        return $this->product && $this->price != $this->product->price;
    }
}

