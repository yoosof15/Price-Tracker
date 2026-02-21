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
        Product::create([
            'name' => 'خیار',
            'image' => 'products/cucumber.svg',
            'color' => '#2ECC71'  // سبز
        ]);
        
        Product::create([
            'name' => 'گوجه فرنگی',
            'image' => 'products/tomato.svg',
            'color' => '#E74C3C'  // قرمز
        ]);
        
        Product::create([
            'name' => 'فلفل دلمه‌ای',
            'image' => 'products/pepper.svg',
            'color' => '#F39C12'  // نارنجی
        ]);
        
        Product::create([
            'name' => 'پیاز',
            'image' => 'products/onion.svg',
            'color' => '#D4AF37'  // طلایی
        ]);
    }
}
