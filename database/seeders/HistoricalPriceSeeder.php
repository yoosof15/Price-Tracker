<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Location;
use App\Models\Price;
use Carbon\Carbon;

class HistoricalPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();
        $locations = Location::all();

        if ($products->isEmpty() || $locations->isEmpty()) {
            $this->command->info('No products or locations found. Please run ProductSeeder and LocationSeeder first.');
            return;
        }

        $endDate = Carbon::today();
        $startDate = Carbon::today()->subMonths(3); // برای 3 ماه گذشته داده ایجاد میکنیم

        $this->command->info('Seeding historical prices for 3 months...');

        foreach ($products as $product) {
            foreach ($locations as $location) {
                $currentDate = $startDate->copy();
                $basePrice = rand(10000, 50000); // یک قیمت پایه تصادفی برای شروع
                
                while ($currentDate->lte($endDate)) {
                    // تغییرات تصادفی قیمت (مثلا +/- 5% از قیمت پایه)
                    $minVariation = rand(-5, 5) / 100;
                    $maxVariation = rand(-5, 5) / 100;

                    $minPrice = $basePrice * (1 + $minVariation);
                    $maxPrice = $basePrice * (1 + $maxVariation);

                    // اطمینان از اینکه maxPrice از minPrice بیشتر باشد
                    if ($minPrice > $maxPrice) {
                        [$minPrice, $maxPrice] = [$maxPrice, $minPrice]; // Swap
                    }
                    // اطمینان از اینکه قیمت ها منفی نشوند
                    $minPrice = max(1000, round($minPrice, -2)); // گرد کردن به نزدیکترین 100
                    $maxPrice = max(1000, round($maxPrice, -2)); // گرد کردن به نزدیکترین 100

                    Price::create([
                        'product_id' => $product->id,
                        'location_id' => $location->id,
                        'date' => $currentDate->toDateString(),
                        'min_price' => $minPrice,
                        'max_price' => $maxPrice,
                    ]);

                    // تغییر قیمت پایه برای روز بعد (برای ایجاد روند)
                    $basePrice += rand(-500, 500); // تغییرات روزانه
                    $basePrice = max(5000, $basePrice); // قیمت از حدی پایین تر نیاید

                    $currentDate->addDay();
                }
            }
        }
        $this->command->info('Historical prices seeded successfully!');
    }
}
