<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'gateway',
        'gateway_method_id',
        'metadata',
        'is_default',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_default' => 'boolean',
    ];

    // 🔁 Relazione con utente
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

