<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([['Keyboard', 49.90], ['Mouse', 19.90], ['Monitor', 899.00], ['Headset', 129.50], ['Webcam', 75.00]] as [$name, $price]) {
            Product::create(['name' => $name, 'price' => $price]);
        }
    }
}
