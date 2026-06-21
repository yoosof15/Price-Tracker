<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Location;
use App\Models\Price;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PriceManagerController extends Controller
{
    private const PER_PAGE = 5;

    public function index()
    {

        if (!auth()->user()->hasPermissionTo('view-price-manager')) {
            abort(403, 'شما اجازه دسترسی به این صفحه را ندارید.');
        }

        $query = $this->basePricesQuery();
        $total = (clone $query)->count();

        $prices = $query
            ->take(self::PER_PAGE)
            ->get()
            ->map(fn (Price $price) => $this->formatPriceRecord($price));

        return Inertia::render('Admin/PriceManager', [
            'prices' => $prices,
            'pricesMeta' => $this->buildPricesMeta(1, $total),
            'selectedDate' => now()->toDateString(),
            'products' => Product::all(),
            'locations' => Location::all(),
        ]);
    }

    public function paginatedPrices(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view-price-manager')) {
            abort(403, 'شما اجازه دسترسی به این صفحه را ندارید.');
        }
        $page = max(1, (int) $request->get('page', 1));

        $query = $this->buildPricesQuery($request);
        $total = (clone $query)->count();

        $prices = $query
            ->skip(($page - 1) * self::PER_PAGE)
            ->take(self::PER_PAGE)
            ->get()
            ->map(fn (Price $price) => $this->formatPriceRecord($price));

        return response()->json([
            'data' => $prices,
            'meta' => $this->buildPricesMeta($page, $total),
        ]);
    }

    public function getPricesList(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view-price-manager')) {
            abort(403, 'شما اجازه دسترسی به این صفحه را ندارید.');
        }
        $prices = DB::table('prices')
            ->join('products', 'prices.product_id', '=', 'products.id')
            ->join('locations', 'prices.location_id', '=', 'locations.id')
            ->select(
                'prices.*',
                'products.name as product_name',
                'locations.name as location_name'
            )
            ->get();

        return response()->json($prices);
    }

    public function getProducts()
    {
        if (!auth()->user()->hasPermissionTo('view-price-manager')) {
            abort(403, 'شما اجازه دسترسی به این صفحه را ندارید.');
        }
        return response()->json(Product::all(['id', 'name']));
    }

    public function getLocations()
    {
        return response()->json(Location::all(['id', 'name', 'currency']));
    }

    public function storeOrUpdate(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('view-price-manager')) {
            abort(403, 'شما اجازه دسترسی به این صفحه را ندارید.');
        }
        $data = $request->validate([
            'id'          => 'nullable|exists:prices,id',
            'product_id'  => 'required',
            'location_id' => 'required',
            'date'        => 'required|date',
            'min_price'   => 'required|numeric',
            'max_price'   => 'required|numeric',
        ]);

        if (!empty($data['id'])) {
            $price = Price::findOrFail($data['id']);
            $price->update($data);

            return response()->json([
                'message' => 'updated',
                'data' => $price,
            ]);
        }

        $price = Price::updateOrCreate(
            [
                'product_id'  => $data['product_id'],
                'location_id' => $data['location_id'],
                'date'        => $data['date'],
            ],
            [
                'min_price' => $data['min_price'],
                'max_price' => $data['max_price'],
            ]
        );

        return response()->json([
            'message' => 'created',
            'data' => $price,
        ]);
    }

    public function destroy(Price $price)
    {
        if (!auth()->user()->hasPermissionTo('view-price-manager')) {
            abort(403, 'شما اجازه دسترسی به این صفحه را ندارید.');
        }
        try {
            $price->delete();
            return response()->json(['success' => true, 'message' => 'رکورد با موفقیت حذف شد.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'خطا در حذف.'], 500);
        }
    }

    public function create()
    {
        if (!auth()->user()->hasPermissionTo('view-price-manager')) {
            abort(403, 'شما اجازه دسترسی به این صفحه را ندارید.');
        }
        return inertia('Prices/Index', ['prices' => [], 'selectedDate' => now()->toDateString()]);
    }

    private function basePricesQuery()
    {
        return Price::with(['product', 'location'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');
    }

    private function buildPricesQuery(Request $request)
    {
        $query = $this->basePricesQuery();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($builder) use ($search) {
                $builder->whereHas('product', function ($productQuery) use ($search) {
                    $productQuery->where('name', 'like', '%' . $search . '%');
                })->orWhereHas('location', function ($locationQuery) use ($search) {
                    $locationQuery->where('name', 'like', '%' . $search . '%');
                });
            });
        }

        if ($request->filled('from_date')) {
            $query->where('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('date', '<=', $request->to_date);
        }

        if ($request->filled('min_price')) {
            $query->where('min_price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('max_price', '<=', $request->max_price);
        }

        return $query;
    }

    private function formatPriceRecord(Price $price): array
    {
        return [
            'id' => (int) $price->id,
            'date' => $price->date,
            'product_id' => (int) $price->product_id,
            'product_name' => $price->product ? $price->product->name : 'محصول حذف شده',
            'location_id' => (int) $price->location_id,
            'location_name' => $price->location ? $price->location->name : 'مکان حذف شده',
            'min_price' => $price->min_price,
            'max_price' => $price->max_price,
        ];
    }

    private function buildPricesMeta(int $page, int $total): array
    {
        return [
            'current_page' => $page,
            'per_page' => self::PER_PAGE,
            'total' => $total,
            'has_more' => ($page * self::PER_PAGE) < $total,
        ];
    }
}
