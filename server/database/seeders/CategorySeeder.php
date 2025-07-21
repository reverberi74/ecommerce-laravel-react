<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // 🧠 Categorie principali con sottocategorie
        $data = [
            'Informatica' => ['Notebook', 'PC', 'Tablet'],
            'Telefonia'   => ['Smartphone', 'Cellulari', 'Power Bank'],
        ];

        foreach ($data as $parentName => $children) {
            $parent = Category::create([
                'name' => $parentName,
                'parent_id' => null,
            ]);

            foreach ($children as $childName) {
                Category::create([
                    'name' => $childName,
                    'parent_id' => $parent->id,
                ]);
            }
        }
    }
}
