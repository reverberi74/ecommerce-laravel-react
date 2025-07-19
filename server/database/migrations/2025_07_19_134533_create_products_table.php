<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // Nome del prodotto
            $table->text('description')->nullable();         // Descrizione (opzionale)
            $table->decimal('price', 8, 2);                  // Prezzo (es. 999999.99)
            $table->unsignedInteger('stock')->default(0);    // Quantità disponibile
            $table->string('image')->nullable();             // Percorso immagine
            $table->string('category')->nullable();          // Categoria prodotto
            $table->timestamps();                            // created_at / updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
