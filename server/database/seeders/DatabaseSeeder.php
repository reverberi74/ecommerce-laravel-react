<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Altri seeder (se presenti)
        $this->call([
            AdminSeeder::class,    // infine: utente admin
            CategorySeeder::class, // prima: genera tutte le categorie e sottocategorie
            ProductSeeder::class,  // poi: assegna una categoria esistente a ogni prodotto
        ]);
    }
}
