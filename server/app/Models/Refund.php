<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'amount',
        'gateway_refund_id',
        'status',
        'reason',
        'refunded_by',
    ];

    // 🔁 Relazione con pagamento
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    // 🔁 Utente che ha eseguito il rimborso (admin)
    public function refundedBy()
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }
}
