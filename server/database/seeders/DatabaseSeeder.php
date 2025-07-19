<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Altri seeder (se presenti)
        $this->call([
            ProductSeeder::class,
        ]);
    }
}
