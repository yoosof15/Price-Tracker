<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'شما باید وارد شوید.');
        }

        // Allow access ONLY if user has access-admin-panel
        if (!$user->hasPermissionTo('access-admin-panel')) {
            abort(403, 'شما اجازه دسترسی به داشبورد را ندارید.');
        }

        // ارائه داده‌های خلاصه برای داشبورد (قابل توسعه)
        return Inertia::render('Admin/Dashboard', [
            'summary' => [
                'products_count' => \App\Models\Product::count(),
                'locations_count' => \App\Models\Location::count(),
                'users_count' => \App\Models\User::count(),
            ]
        ]);
    }
}
