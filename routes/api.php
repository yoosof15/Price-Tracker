<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PriceController;

Route::get('/prices/today', [PriceController::class, 'today']);

// روت‌های مخصوص کاربر لاگین کرده
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
