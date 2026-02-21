<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyPriceSetting;
use App\Models\Product;
use App\Models\Location;
use App\Models\Price; 
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class DailyPriceSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth'); // نیاز به لاگین
    }

    /**
     * Get all active product-location pairs for the current day.
     */
    public function index()
    {
        // <--- چک مستقیم دسترسی
        if (!auth()->user()->hasPermissionTo('view-daily-price-settings')) {
            abort(403, 'شما اجازه دسترسی به این صفحه را ندارید.');
        }

        $today = Carbon::today();
        $settings = DailyPriceSetting::where('date', $today)
                                   ->with(['product', 'location'])
                                   ->get();
        return response()->json($settings);
    }

    /**
     * Add a product-location pair for the current day.
     */
    public function store(Request $request)
    {
                // <--- چک مستقیم دسترسی
        if (!auth()->user()->hasPermissionTo('add-to-daily-price-settings')) {
            abort(403, 'شما اجازه دسترسی به این صفحه را ندارید.');
        }

        $request->validate([
            'product_id' => Rule::requiredIf($request->location_id === null) . '|nullable|exists:products,id',
            'location_id' => Rule::requiredIf($request->product_id === null) . '|nullable|exists:locations,id',
        ]);

        if ($request->product_id === null && $request->location_id === null) {
            return response()->json(['message' => 'Either product_id or location_id must be provided.'], 422);
        }
        if ($request->product_id !== null && $request->location_id !== null) {
            return response()->json(['message' => 'Cannot provide both product_id and location_id. Choose one.'], 422);
        }

        $today = Carbon::today();

        $setting = DailyPriceSetting::firstOrCreate(
            [
                'product_id' => $request->product_id,
                'location_id' => $request->location_id,
                'date' => $today,
            ]
        );

        return response()->json($setting->load(['product', 'location']), 201);
    }

    /**
     * Remove a product-location pair for the current day.
     * Also deletes any existing prices for that specific product, location, and date.
     */
    public function destroy($type, $id)
    {
                // <--- چک مستقیم دسترسی
        if (!auth()->user()->hasPermissionTo('remove-from-daily-price-settings')) {
            abort(403, 'شما اجازه دسترسی به این صفحه را ندارید.');
        }

        $today = Carbon::today();

        // <--- پیدا کردن DailyPriceSetting برای حذف
        $dailySettingQuery = DailyPriceSetting::where('date', $today);
        if ($type === 'product') {
            $dailySettingQuery->where('product_id', $id)->whereNull('location_id');
        } elseif ($type === 'location') {
            $dailySettingQuery->where('location_id', $id)->whereNull('product_id');
        } else {
            return response()->json(['message' => 'Invalid delete type provided.'], 400);
        }
        
        $dailySetting = $dailySettingQuery->first(); // <--- پیدا کردن رکورد

        if (!$dailySetting) {
            return response()->json(['message' => 'تنظیمات مورد نظر برای امروز پیدا نشد.'], 404);
        }

        // <--- حذف تمام قیمت‌های ثبت شده برای این ترکیب محصول/مکان/تاریخ
        if ($type === 'product') {
            Price::where('product_id', $id)
                   ->where('date', $today)
                   ->delete();
        } elseif ($type === 'location') {
            Price::where('location_id', $id)
                   ->where('date', $today)
                   ->delete();
        }
        
        // <--- حذف رکورد از DailyPriceSetting
        $dailySetting->delete();

        return response()->json(null, 204);
    }
}
