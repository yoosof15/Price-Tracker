<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\DailyPriceSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;


class ProductController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth'); // نیاز به لاگین
    }

    public function show(Product $product)
    {
        if (!auth()->user()->hasPermissionTo('view-products')) {
            abort(403, 'شما اجازه دسترسی به این محصول را ندارید.');
        }
        return response()->json($product);
    }

    public function update(Request $request, Product $product)
    {
        auth()->user()->loadMissing('role.permissions'); 
        if (!auth()->user()->hasPermissionTo('edit-product')) {
            abort(403, 'شما اجازه ویرایش محصول را ندارید.');
        }

        $request->validate([
            'name' => ['required','string','max:255', Rule::unique('products','name')->ignore($product->id)],
            'image' => 'nullable|image|max:2048',
            'color' => 'nullable|string|max:20',
        ]);

        $imagePath = $product->image;
        if ($request->hasFile('image')) {
            // حذف عکس قدیمی در صورت وجود
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product->update([
            'name' => $request->name,
            'image' => $imagePath,
            'color' => $request->color,
        ]);

        return response()->json($product);
    }

    public function index()
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('view-products') && !$user->hasPermissionTo('view-dashboard')) {
            abort(403, 'شما اجازه دسترسی به این صفحه را ندارید.');
        }
        return Inertia::render('Admin/ProductList', [
            'products' => Product::all()
        ]);
    }

    public function store(Request $request)
    {
        // <--- بارگذاری صریح role.permissions
        auth()->user()->loadMissing('role.permissions'); 
        if (!auth()->user()->hasPermissionTo('create-product')) {
            abort(403, 'شما اجازه ایجاد محصول را ندارید.');
        }
        $request->validate([
            'name' => 'required|string|max:255|unique:products,name',
            'image' => 'nullable|image|max:2048',
            'color' => 'nullable|string|max:20',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'name' => $request->name,
            'image' => $imagePath,
            'color' => $request->color,
        ]);

        return response()->json($product, 201);
    }

    public function destroy(Product $product)
    {
        // <--- بارگذاری صریح role.permissions
        auth()->user()->loadMissing('role.permissions'); 
        if (!auth()->user()->hasPermissionTo('delete-product')) {
            abort(403, 'شما اجازه حذف محصول را ندارید.');
        }
        $product->delete();
        return response()->json(null, 204);
    }
}
