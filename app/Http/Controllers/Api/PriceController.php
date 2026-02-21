<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Price;
use App\Models\Location; // <--- این را هم نیاز داریم
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Morilog\Jalali\Jalali; // <--- این خط تنها use مربوط به Jalali است
use App\Models\DailyPriceSetting;

class PriceController extends Controller
{
    /**
     * Get a list of products with today's prices for the admin form.
     * Includes product's prices for today, if any, and eager loads location names.
     */
    public function getProducts()
    {
        $today = Carbon::today();
        $products = Product::all();
        $activeProductIds = DailyPriceSetting::where('date', $today)
                                          ->pluck('product_id')
                                          ->unique();

        return response()->json($products->map(function ($product) use ($activeProductIds) {
            $product->is_active_for_today = $activeProductIds->contains($product->id);
            return $product;
        }));
    }

    public function getLocations() // <--- متد جدید/اصلاح شده
    {
        $today = Carbon::today();
        $locations = Location::all();
        $activeLocationIds = DailyPriceSetting::where('date', $today)
                                            ->pluck('location_id')
                                            ->unique();

        return response()->json($locations->map(function ($location) use ($activeLocationIds) {
            $location->is_active_for_today = $activeLocationIds->contains($location->id);
            return $location;
        }));
    }


    public function getPublicLocations() // <--- متد جدید
    {
        return response()->json(Location::all());
    }

    /**
     * Get today's prices for the public price list page.
     * Restructures data for dynamic display.
     */
    public function today()
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // <--- فقط DailyPriceSetting های امروز را می گیریم
        $dailySettings = DailyPriceSetting::where('date', $today)->get();
        $activeProductIds = $dailySettings->pluck('product_id')->unique();
        $activeLocationIds = $dailySettings->pluck('location_id')->unique();

        // اگر هیچ DailyPriceSetting ای برای امروز نباشد، لیست خالی برمی گردانیم
        if ($activeProductIds->isEmpty() || $activeLocationIds->isEmpty()) {
            return response()->json([]);
        }

        // <--- فقط محصولاتی را که امروز فعال هستند، واکشی می کنیم
        $products = Product::whereIn('id', $activeProductIds)
                           ->with(['prices' => function ($query) use ($today, $yesterday, $activeLocationIds) {
                               // <--- فقط قیمت های مربوط به مکان های فعال امروز را می گیریم
                               $query->whereIn('location_id', $activeLocationIds)
                                     ->whereIn('date', [$today, $yesterday]);
                           }])->get();

        $result = $products->map(function ($product) use ($today, $yesterday, $activeLocationIds) {
            $todayPrices = $product->prices->where('date', $today->toDateString())->keyBy('location_id');
            $yesterdayPrices = $product->prices->where('date', $yesterday->toDateString())->keyBy('location_id');

            $mappedPrices = [];
            // <--- فقط برای مکان های فعال امروز، داده ها را آماده میکنیم
            foreach (Location::whereIn('id', $activeLocationIds)->get() as $location) {
                $locationId = $location->id;
                
                $todayMin = $todayPrices->get($locationId) ? $todayPrices->get($locationId)->min_price : null;
                $todayMax = $todayPrices->get($locationId) ? $todayPrices->get($locationId)->max_price : null;

                $yesterdayMin = $yesterdayPrices->get($locationId) ? $yesterdayPrices->get($locationId)->min_price : null;
                $yesterdayMax = $yesterdayPrices->get($locationId) ? $yesterdayPrices->get($locationId)->max_price : null;
                
                $todayAvg = ($todayMin !== null && $todayMax !== null) ? ($todayMin + $todayMax) / 2 : null;
                $yesterdayAvg = ($yesterdayMin !== null && $yesterdayMax !== null) ? ($yesterdayMin + $yesterdayMax) / 2 : null;

                $priceChange = 0; // 0: بدون تغییر، 1: گرانتر، -1: ارزانتر

                if ($todayAvg !== null && $yesterdayAvg !== null) {
                    if ($todayAvg > $yesterdayAvg) {
                        $priceChange = 1;
                    } elseif ($todayAvg < $yesterdayAvg) {
                        $priceChange = -1;
                    }
                }

                $mappedPrices[$locationId] = [
                    'min_price' => $todayMin,
                    'max_price' => $todayMax,
                    'change'    => $priceChange,
                ];
            }
            return [
                'id' => $product->id,
                'product_name' => $product->name,
                'image' => $product->image, // path relative to storage (e.g. products/xxx.jpg)
                'color' => $product->color,
                'prices' => $mappedPrices,
            ];
        });

        return response()->json($result);
    }


    /**
     * Store or update prices for products for the current day.
     */
    public function store(Request $request)
    {
        // dd($request->all()); // <--- این خط رو حذف کن

        $request->validate([
            'prices.*.product_id' => 'required|exists:products,id',
            'prices.*.location_id' => 'required|exists:locations,id',
            'prices.*.min_price' => 'nullable|integer',
            'prices.*.max_price' => 'nullable|integer',
        ]);

        $today = Carbon::today();

        // <--- مرحله اصلاح شده: اطمینان حاصل میکنیم که فقط برای DailyPriceSetting های امروز ذخیره شود
        $dailyProductSettings = DailyPriceSetting::where('date', $today)->whereNotNull('product_id')->pluck('product_id')->toArray();
        $dailyLocationSettings = DailyPriceSetting::where('date', $today)->whereNotNull('location_id')->pluck('location_id')->toArray();
        
        // <--- ایجاد یک ست (Set) برای جستجوی سریعتر
        $activeProductIdsForToday = collect($dailyProductSettings)->map(fn($id) => (int)$id);
        $activeLocationIdsForToday = collect($dailyLocationSettings)->map(fn($id) => (int)$id);


        foreach ($request->prices as $priceData) {
            // <--- اگر این ترکیب product_id در DailyPriceSetting Products برای امروز فعال نباشد، رد میکنیم
            // <--- یا اگر location_id در DailyPriceSetting Locations برای امروز فعال نباشد، رد میکنیم
            if (!$activeProductIdsForToday->contains($priceData['product_id']) || !$activeLocationIdsForToday->contains($priceData['location_id'])) {
                continue;
            }

            $minPrice = $priceData['min_price'] ?? null;
            $maxPrice = $priceData['max_price'] ?? null;

            if ($minPrice === null && $maxPrice === null) {
                Price::where('product_id', $priceData['product_id'])
                     ->where('location_id', $priceData['location_id'])
                     ->where('date', $today)
                     ->delete();
            } else {
                Price::updateOrCreate(
                    [
                        'product_id' => $priceData['product_id'],
                        'location_id'   => $priceData['location_id'],
                        'date'       => $today,
                    ],
                    [
                        'min_price'  => $minPrice,
                        'max_price'  => $maxPrice,
                    ]
                );
            }
        }

        return response()->json(['message' => 'قیمت‌ها با موفقیت ثبت شد.'], 200);
    }



    /**
     * Fetch historical price data for a given product.
     * @param Request $request
     * @param Product $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function history(Request $request, Product $product)
    {
        $request->validate([
            'period' => 'sometimes|in:daily,weekly,monthly,yearly',
        ]);

        $period = $request->input('period', 'daily');

        $query = Price::where('product_id', $product->id)
                      ->with('location')
                      ->orderBy('date');

        $prices = $query->get();

        // تجمیع داده‌ها بر اساس دوره
        $aggregatedData = $this->aggregatePrices($prices, $period);

        return response()->json($aggregatedData);
    }

    /**
     * Helper function to aggregate prices by period.
     * @param \Illuminate\Support\Collection $prices
     * @param string $period
     * @return array
     */
    protected function aggregatePrices($prices, $period)
    {
        $data = [];
        $groupedPrices = $prices->groupBy(function($price) use ($period) {
            $carbonDate = Carbon::parse($price->date);

            switch ($period) {
                case 'daily':
                    return jdate($carbonDate)->format('Y/m/d');
                case 'weekly':
                    // شروع هفته شمسی (شنبه)
                    $weekStart = $carbonDate->startOfWeek(Carbon::SATURDAY);
                    return jdate($weekStart)->format('Y/m/d') . ' (هفته)';
                case 'monthly':
                    return jdate($carbonDate)->format('Y/m');
                case 'yearly':
                    return jdate($carbonDate)->format('Y');
                default:
                    return jdate($carbonDate)->format('Y/m/d');
            }
        });

        // ساختار دهی داده ها برای نمودار
        $chartData = [];
        foreach ($groupedPrices as $dateKey => $group) {
            $entry = ['date' => $dateKey];
            $locationsMinMax = [];

            // این بخش برای هر مکان، حداقل و حداکثر قیمت را در آن دوره جمع آوری می کند
            foreach ($group as $price) {
                $locationName = $price->location->name;
                if (!isset($locationsMinMax[$locationName])) {
                    $locationsMinMax[$locationName] = ['min' => PHP_INT_MAX, 'max' => 0];
                }
                $locationsMinMax[$locationName]['min'] = min($locationsMinMax[$locationName]['min'], $price->min_price);
                $locationsMinMax[$locationName]['max'] = max($locationsMinMax[$locationName]['max'], $price->max_price);
            }

            // فرمت دهی برای خروجی نهایی
            foreach ($locationsMinMax as $locationName => $values) {
                $entry["min_{$locationName}"] = $values['min'];
                $entry["max_{$locationName}"] = $values['max'];
            }
            $chartData[] = $entry;
        }

        return $chartData;
    }
}
