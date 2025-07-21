<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Category;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'image',
        'category_id', // <- correzione
    ];

    // RELAZIONE: ogni prodotto appartiene a una categoria
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
