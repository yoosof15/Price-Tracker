<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Api\PriceController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\DailyPriceSettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PriceManagerController;
use App\Models\User;
use App\Models\Role;
use App\Models\Product;
use App\Models\Location;
use App\Models\Permission; // <--- برای RoleController@update (در صورت استفاده)


/*
|--------------------------------------------------------------------------
| Public Routes (دسترسی برای همه، لاگین کرده یا نکرده)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('PriceList');
})->name('pricelist');

Route::get('/api/prices/today', [PriceController::class, 'today']);
Route::get('/admin/api/locations', [PriceController::class, 'getLocations']);
Route::get('/admin/api/products', [PriceController::class, 'getProducts'])->name('admin.api.products');
Route::get('/api/prices/history/{product}', [PriceController::class, 'history']);
Route::get('/api/locations', [PriceController::class, 'getPublicLocations']);


/*
|--------------------------------------------------------------------------
| \Set Prices Routes (دسترسی برای همه)
|--------------------------------------------------------------------------
*/
// مسیر اصلی صفحه قیمت‌ها (بدون auth)
Route::get('/prices', [PriceManagerController::class, 'index'])->name('prices.index');
Route::get('/prices/create', [PriceManagerController::class, 'create'])->name('prices.create'); // اختیاری

Route::get('/api/prices/list', [PriceManagerController::class, 'getPricesList']);

// مسیرهای API برای عملیات
Route::get('/api/prices/products', [PriceManagerController::class, 'getProducts']);
Route::get('/api/prices/locations', [PriceManagerController::class, 'getLocations']);
Route::post('/api/prices/store-or-update', [PriceManagerController::class, 'storeOrUpdate']);
Route::delete('/api/prices/{price}', [PriceManagerController::class, 'destroy']);



/*
|--------------------------------------------------------------------------
| Authenticated Routes (دسترسی فقط برای کاربران لاگین کرده)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    // صفحه داشبورد اصلی
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // مدیریت پروفایل کاربر
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // <--- روت برای ثبت قیمت‌ها (نیاز به view-dashboard)
    Route::get('/admin/prices', function (Request $request) {
        $user = $request->user();
        if (!$user->hasPermissionTo('view-dashboard')) {
            abort(403, 'شما اجازه دسترسی به صفحه ثبت قیمت‌ها را ندارید.');
        }
        return Inertia::render('Admin/PriceEntry');
    })->name('admin.prices');

    // <--- روت برای نمایش صفحه ProductList.vue (نیاز به view-dashboard)
    Route::get('/admin/products/list', [ProductController::class, 'index'])->name('admin.products.index');

    // <--- روت برای صفحه LocationList.vue (نیاز به view-dashboard)
    Route::get('/admin/locations/list', [LocationController::class, 'index'])->name('admin.locations.index');


    // <--- روت برای صفحه RoleList.vue (فقط view)
    Route::get('/admin/roles/list', [RoleController::class, 'index'])->name('admin.roles.index');
    // <--- API های دسترسی ها (برای واکشی لیست دسترسی ها) - فقط view
    Route::get('/admin/permissions', [PermissionController::class, 'index'])->name('admin.permissions.index');


    /*
    |--------------------------------------------------------------------------
    | Admin API Routes (فقط برای Super Admin یا کاربران با دسترسی خاص)
    |--------------------------------------------------------------------------
    */
    // <--- این گروه برای API هایی است که عملیات CRUD را انجام میدهند
    Route::prefix('admin')->middleware(['auth'])->group(function () { // <--- middleware auth
        // API های مدیریت محصولات
        Route::post('/products', [ProductController::class, 'store'])->name('admin.products.store');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('admin.products.destroy');
        Route::get('/products/{product}', [ProductController::class, 'show'])->name('admin.products.show');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('admin.products.update');
        // (روت ویرایش محصول را هم بعدا اضافه میکنیم)

        // API های مدیریت مکان ها
        Route::post('/locations', [LocationController::class, 'store'])->name('admin.locations.store');
        Route::delete('/locations/{location}', [LocationController::class, 'destroy'])->name('admin.locations.destroy');
        Route::get('/locations/{location}', [LocationController::class, 'show'])->name('admin.locations.show');
        Route::put('/locations/{location}', [LocationController::class, 'update'])->name('admin.locations.update');
        // (روت ویرایش مکان را هم بعدا اضافه میکنیم)

        // API های مدیریت Daily Price Settings
        Route::get('/daily-settings', [DailyPriceSettingController::class, 'index'])->name('admin.daily-settings.index'); // <--- این باید اینجا باشد
        Route::post('/daily-settings', [DailyPriceSettingController::class, 'store'])->name('admin.daily-settings.store');
        Route::delete('/daily-settings/{type}/{id}', [DailyPriceSettingController::class, 'destroy'])->name('admin.daily-settings.destroy');

        // API ذخیره قیمت های روزانه
        Route::post('/prices', [PriceController::class, 'store']);

        // API های مدیریت نقش ها
        Route::post('/roles', [RoleController::class, 'store'])->name('admin.roles.store');
        Route::get('/roles/{role}', [RoleController::class, 'show'])->name('admin.roles.show'); // <--- این هم اینجا
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('admin.roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('admin.roles.destroy');
        Route::post('/roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->name('admin.roles.sync-permissions');

        // API های مدیریت کاربران
        Route::post('/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
        Route::put('/users/{user}/role', [UserController::class, 'updateRole'])->name('admin.users.update-role');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('admin.users.show');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('admin.users.update');




    });


    Route::middleware(['auth'])->name('admin.')->prefix('admin')->group(function () {
        // ... مسیرهای دیگر ...

        // صفحه مدیریت قیمت‌ها (لیست / ویرایش / حذف)
        Route::get('/price-manager', [PriceManagerController::class, 'index'])->name('price_manager');
        Route::get('/price-manager/prices', [PriceManagerController::class, 'paginatedPrices'])->name('price_manager.prices');
    });


    /*
    |--------------------------------------------------------------------------
    | Super Admin-Only Routes (دسترسی فقط برای مدیر کل)
    |--------------------------------------------------------------------------
    */
    // <--- این گروه فقط برای UserList
    Route::prefix('admin')->middleware(['is_super_admin'])->group(function () {
        // صفحه مدیریت کاربران
            Route::get('/users', [UserController::class, 'index'])->name('admin.users.index'); // <--- این روت برای نمایش صفحه UserList.vue است
    });

});

require __DIR__.'/auth.php';
