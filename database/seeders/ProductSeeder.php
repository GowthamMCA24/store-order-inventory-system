<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;


class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       Product::insert([
            [
                'name' => 'Rice Bag',
                'code' => 'PRD-001',
                'price' => 1200.00,
                'tax_percentage' => 5.00,
                'stock' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cooking Oil',
                'code' => 'PRD-002',
                'price' => 180.00,
                'tax_percentage' => 5.00,
                'stock' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Sugar',
                'code' => 'PRD-003',
                'price' => 45.00,
                'tax_percentage' => 5.00,
                'stock' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
