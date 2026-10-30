<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         Customer::insert([
            [
                'name' => 'Arun Kumar',
                'email' => 'arun@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ravi Kumar',
                'email' => 'ravi@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Priya Devi',
                'email' => 'priya@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
