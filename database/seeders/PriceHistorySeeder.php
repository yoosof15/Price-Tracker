<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Price;
use App\Models\Product;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PriceHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure products and locations exist
        $products = Product::take(6)->get();
        $locations = Location::take(4)->get();

        if ($products->count() < 6 || $locations->count() < 4) {
            $this->command->error('Require at least 6 products and 4 locations in database.');
            return;
        }

        $start = Carbon::today()->subYears(2);
        $end = Carbon::today();

        $this->command->info('Seeding prices from '.$start->toDateString().' to '.$end->toDateString());

        DB::beginTransaction();
        try {
            $date = $start->copy();
            $bulk = [];

            while ($date->lte($end)) {
                foreach ($products as $p) {
                    foreach ($locations as $l) {
                        // Generate a base price per product-location using a deterministic seed
                        $baseSeed = crc32($p->id . '|' . $l->id) % 50000 + 10000; // baseline between 10k-60k

                        // Add seasonal and random variation
                        $dayOfYear = (int)$date->format('z');
                        $seasonFactor = sin(($dayOfYear / 365) * 2 * pi());

                        $avg = $baseSeed + ($seasonFactor * 5000) + rand(-1500, 1500);
                        $min = max(1000, (int)round($avg - rand(500, 2000)));
                        $max = max($min + 100, (int)round($avg + rand(500, 2000)));

                        $bulk[] = [
                            'product_id' => $p->id,
                            'location_id' => $l->id,
                            'min_price' => $min,
                            'max_price' => $max,
                            'date' => $date->toDateString(),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        // flush bulk to DB periodically to avoid memory issues
                        if (count($bulk) >= 1000) {
                            Price::insert($bulk);
                            $bulk = [];
                        }
                    }
                }

                $date->addDay();
            }

            if (count($bulk) > 0) {
                Price::insert($bulk);
            }

            DB::commit();
            $this->command->info('Price history seeded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Seeding failed: ' . $e->getMessage());
        }
    }
}
