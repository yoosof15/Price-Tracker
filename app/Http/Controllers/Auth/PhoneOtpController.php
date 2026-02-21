<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;
use Inertia\Response as InertiaResponse;

class PhoneOtpController extends Controller
{
    public function createRequest(): Response
    {
        return Inertia::render('Auth/PhoneOtpRequest', [
            'status' => session('status'),
        ]);
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'phone:IR', 'exists:users,phone'],
        ], [
            'phone.required' => 'شماره موبایل الزامی است.',
            'phone.phone' => 'شماره موبایل معتبر نیست.',
            'phone.exists' => 'این شماره موبایل ثبت نشده است.',
        ]);

        $phone = $request->string('phone');
        $code = (string) random_int(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(5);

        DB::table('login_otp_tokens')->updateOrInsert(
            ['phone' => $phone],
            [
                'code_hash' => Hash::make($code),
                'expires_at' => $expiresAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Send OTP via MeliPayamak Pattern API
        try {
            $response = Http::asForm()->post('https://api.payamak-panel.com/post/send.asmx/SendByBaseNumber2', [
                'username' => config('app.melipayamak_username'),
                'password' => config('app.melipayamak_password'),
                'to' => $phone,
                'bodyId' => config('app.melipayamak_pattern_id'),
                'text' => "$phone;$code"
            ]);

            if (!$response->successful()) {
                return back()->withErrors(['phone' => 'خطا در ارسال کد تأیید.']);
            }
        } catch (\Exception $e) {
            return back()->withErrors(['phone' => 'خطا در ارسال کد تأیید: ' . $e->getMessage()]);
        }

        // Generate secure token for verify page
        $token = bin2hex(random_bytes(32));
        session(['otp_verify_token' => $token, 'otp_verify_phone' => $phone]);

        return redirect()->route('phone.otp.verify', ['token' => $token])
            ->with('status', 'کد تأیید ارسال شد.');
    }

    public function createVerify(Request $request): InertiaResponse|RedirectResponse
    {
        $token = $request->query('token');
        
        // Validate token
        if (!$token || $token !== session('otp_verify_token')) {
            return redirect()->route('phone.otp.request')
                ->withErrors(['phone' => 'لینک نامعتبر یا منقضی شده است. لطفاً دوباره تلاش کنید.']);
        }

        $phone = session('otp_verify_phone');
        
        if (!$phone) {
            return redirect()->route('phone.otp.request')
                ->withErrors(['phone' => 'لطفاً ابتدا شماره موبایل خود را وارد کنید.']);
        }

        return Inertia::render('Auth/PhoneOtpVerify', [
            'phone' => $phone,
            'token' => $token,
            'status' => session('status'),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        // Validate token first
        $token = $request->input('token');
        if (!$token || $token !== session('otp_verify_token')) {
            return redirect()->route('phone.otp.request')
                ->withErrors(['phone' => 'لینک نامعتبر یا منقضی شده است.']);
        }

        $phone = session('otp_verify_phone');
        if (!$phone) {
            return redirect()->route('phone.otp.request')
                ->withErrors(['phone' => 'لطفاً ابتدا شماره موبایل خود را وارد کنید.']);
        }

        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ], [
            'code.required' => 'کد تأیید الزامی است.',
            'code.size' => 'کد تأیید باید 6 رقم باشد.',
        ]);

        $record = DB::table('login_otp_tokens')->where('phone', $phone)->first();
        if (! $record) {
            // Clear session and redirect
            session()->forget(['otp_verify_token', 'otp_verify_phone']);
            return redirect()->route('phone.otp.request')
                ->withErrors(['phone' => 'کد معتبر نیست. لطفاً دوباره درخواست دهید.']);
        }

        if (Carbon::parse($record->expires_at)->isPast()) {
            DB::table('login_otp_tokens')->where('phone', $phone)->delete();
            session()->forget(['otp_verify_token', 'otp_verify_phone']);
            return redirect()->route('phone.otp.request')
                ->withErrors(['phone' => 'کد منقضی شده است. لطفاً دوباره درخواست دهید.']);
        }

        if (! Hash::check($request->string('code'), $record->code_hash)) {
            return back()->withErrors(['code' => 'کد وارد شده اشتباه است.']);
        }

        // Success - clean up
        DB::table('login_otp_tokens')->where('phone', $phone)->delete();
        session()->forget(['otp_verify_token', 'otp_verify_phone']);

        $user = User::where('phone', $phone)->first();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
