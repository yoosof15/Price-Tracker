<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

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
        auth()->user()->loadMissing('role.permissions');
        if (!auth()->user()->hasPermissionTo('create-product')) {
            abort(403, 'شما اجازه ایجاد محصول را ندارید.');
        }

        $request->validate([
            'name'  => 'required|string|max:255|unique:products,name',
            'image' => 'nullable|image|max:2048',
            'color' => 'nullable|string|max:20',
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');

            // اطمینان از وجود پوشه public/storage/products
            $dir = public_path('storage/products');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $name = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
            $file->move($dir, $name);

            // ⚠️ طبق خواسته شما، داخل DB فقط products/... ذخیره می‌شود
            $imagePath = 'products/' . $name;
        }

        $product = Product::create([
            'name'  => $request->name,
            'image' => $imagePath,
            'color' => $request->color,
        ]);

        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product)
    {
        auth()->user()->loadMissing('role.permissions');
        if (!auth()->user()->hasPermissionTo('edit-product')) {
            abort(403, 'شما اجازه ویرایش محصول را ندارید.');
        }

        $request->validate([
            'name'  => ['required', 'string', 'max:255', Rule::unique('products', 'name')->ignore($product->id)],
            'image' => 'nullable|image|max:2048',
            'color' => 'nullable|string|max:20',
        ]);

        $imagePath = $product->image;

        if ($request->hasFile('image')) {
            // ✅ حذف عکس قبلی (اگر وجود دارد)
            if (!empty($product->image)) {
                // چون در DB: products/xxx.jpg
                // ولی فایل واقعی در: public/storage/products/xxx.jpg
                $oldFullPath = public_path('storage/' . $product->image);

                if (is_file($oldFullPath)) {
                    @unlink($oldFullPath);
                }
            }

            $file = $request->file('image');

            // اطمینان از وجود پوشه public/storage/products
            $dir = public_path('storage/products');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $name = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
            $file->move($dir, $name);

            // طبق خواسته شما
            $imagePath = 'products/' . $name;
        }

        $product->update([
            'name'  => $request->name,
            'image' => $imagePath,
            'color' => $request->color,
        ]);

        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        auth()->user()->loadMissing('role.permissions');
        if (!auth()->user()->hasPermissionTo('delete-product')) {
            abort(403, 'شما اجازه حذف محصول را ندارید.');
        }

        // ✅ قبل از حذف محصول، عکسش هم حذف شود
        if (!empty($product->image)) {
            $fullPath = public_path('storage/' . $product->image);
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }

        $product->delete();

        return response()->json(null, 204);
    }
}