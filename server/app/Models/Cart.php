<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
    ];

    // 🔁 Relazione con User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🔁 Relazione con cart_items
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    // 📦 Totale importo carrello (calcolato on-the-fly)
    public function getTotalAmountAttribute()
    {
        return $this->items->sum(fn($item) => $item->quantity * $item->price);
    }

    // 📦 Conteggio item (per badge carrello)
    public function getItemsCountAttribute()
    {
        return $this->items->sum('quantity');
    }
}

