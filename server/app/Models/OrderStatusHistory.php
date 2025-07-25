<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderStatusHistory extends Model
{
    use HasFactory;

    public $timestamps = false; // Usiamo solo created_at manualmente

    protected $fillable = [
        'order_id',
        'old_status',
        'new_status',
        'changed_by',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // 🔗 Relazioni

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'changed_by')->withTrashed(); // anche se admin eliminato
    }
}
