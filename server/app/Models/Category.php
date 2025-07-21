<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'parent_id'];

    // 🔁 RELAZIONI

    // Sottocategorie (una categoria può avere più figli)
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Categoria padre (una sottocategoria ha un padre)
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Prodotti associati a questa categoria/sottocategoria
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
