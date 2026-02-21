<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\PhoneOtpController;
// use App\Http\Controllers\Auth\EmailVerificationNotificationController; // <--- این خط حذف شد
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
// use App\Http\Controllers\Auth\PasswordResetLinkController; // <--- این خط حذف شد
// use App\Http\Controllers\Auth\RegisteredUserController; // <--- این خط حذف شد (از قبل)
// use App\Http\Controllers\Auth\VerifyEmailController; // <--- این خط حذف شد
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('login/otp', [PhoneOtpController::class, 'createRequest'])
                ->name('phone.otp.request');
    Route::post('login/otp', [PhoneOtpController::class, 'sendCode'])
                ->name('phone.otp.send');

    Route::get('login/otp/verify', [PhoneOtpController::class, 'createVerify'])
                ->name('phone.otp.verify');
    Route::post('login/otp/verify', [PhoneOtpController::class, 'verify'])
                ->name('phone.otp.verify.submit');
});

Route::middleware('auth')->group(function () {
    // <--- تمام روت های مربوط به Email Verification حذف شدند
    // Route::get('verify-email', [EmailVerificationNotificationController::class])
    //             ->middleware('throttle:6,1')
    //             ->name('verification.notice');

    // Route::get('verify-email/{id}/{hash}', [VerifyEmailController::class])
    //             ->middleware(['signed', 'throttle:6,1'])
    //             ->name('verification.verify');

    // Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    //             ->middleware('throttle:6,1')
    //             ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
                ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');
});
