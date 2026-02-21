<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location; // <-- Model Location را اضافه میکنیم
use Illuminate\Http\Request;
use Illuminate\Validation\Rule; // <-- برای اعتبارسنجی unique
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;


class LocationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth'); // نیاز به لاگین
    }
    /**
     * Display a listing of the locations.
     */
    public function index()
    {
        $user = auth()->user();
        if (!$user->hasPermissionTo('view-locations') && !$user->hasPermissionTo('view-dashboard')) {
            abort(403, 'شما اجازه دسترسی به این صفحه را ندارید.');
        }
        return Inertia::render('Admin/LocationList', [
            'locations' => Location::all()
        ]);
    }

    /**
     * Store a newly created location in storage.
     */
    public function store(Request $request)
    {
        if (!auth()->user()->hasPermissionTo('create-location')) { // <--- چک مستقیم
            abort(403, 'شما اجازه ایجاد مکان جدید را ندارید.');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:locations,name', // <-- نام مکان باید یکتا باشد
            'currency' => 'required|string|max:20', // <-- واحد پول
        ]);

        $location = Location::create([
            'name' => $request->name,
            'currency' => $request->currency,
        ]);

        return response()->json($location, 201); // 201: Created
    }

    /**
     * Remove the specified location from storage.
     */
    public function destroy(Location $location) // <-- لاراول به صورت خودکار Location را پیدا میکند
    {
         if (!auth()->user()->hasPermissionTo('delete-location')) { // <--- چک مستقیم
            abort(403, 'شما اجازه حذف مکان را ندارید.');
        }
        try {
            $location->delete();
            return response()->json(null, 204); // 204: No Content
        } catch (\Exception $e) {
            // اگر مکانی که قیمت به آن وابسته است حذف شود، خطا میدهد
            return response()->json(['message' => 'این مکان دارای قیمت‌های ثبت شده است و قابل حذف نیست.'], 409); // 409: Conflict
        }
    }

    /**
     * Return a single location as JSON (for edit forms).
     */
    public function show(Location $location)
    {
        if (!auth()->user()->hasPermissionTo('view-locations')) {
            abort(403, 'شما اجازه مشاهده مکان را ندارید.');
        }
        return response()->json($location);
    }

    /**
     * Update the specified location in storage.
     */
    public function update(Request $request, Location $location)
    {
        if (!auth()->user()->hasPermissionTo('edit-location')) {
            abort(403, 'شما اجازه ویرایش مکان را ندارید.');
        }

        $request->validate([
            'name' => ['required','string','max:255', Rule::unique('locations','name')->ignore($location->id)],
            'currency' => 'required|string|max:20',
        ]);

        $location->update([
            'name' => $request->name,
            'currency' => $request->currency,
        ]);

        return response()->json($location);
    }
}
