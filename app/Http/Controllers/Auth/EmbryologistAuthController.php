<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class EmbryologistAuthController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function showLoginForm()
    {
        if (Auth::check()) {
            return Auth::user()->isAdmin()
                ? redirect()->route('admin.dashboard')
                : redirect()->route('embryologist.dashboard');
        }

        return view('auth.embryologist_login');
    }

    public function submitCredentials(Request $request)
    {
        // 4 Mandatory Credentials Every Time
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'special_key' => ['required', 'string'],
        ], [
            'name.required' => 'Full Name is a mandatory authentication credential.',
            'email.required' => 'Registered Email Address is mandatory.',
            'password.required' => 'Password is mandatory.',
            'special_key.required' => 'Unique Special Key is mandatory for every embryologist login.',
        ]);

        $throttleKey = 'embryo_login|' . $request->ip() . '|' . $request->email;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'auth_failed' => "Too many failed authentication attempts. Account access is locked for {$seconds} seconds.",
            ]);
        }

        $user = User::where('email', $request->email)
            ->where('role', 'embryologist')
            ->first();

        // Validate: Email exists
        if (!$user) {
            RateLimiter::hit($throttleKey, 300);
            ActivityLog::record(null, 'embryologist_login_failed', null, [
                'email' => $request->email,
                'reason' => 'email_not_found',
            ]);
            return back()->withErrors([
                'auth_failed' => 'Authentication failed. Please verify all four laboratory credentials.',
            ])->withInput($request->only('name', 'email'));
        }

        // Validate: Name matches account (case-insensitive trimmed comparison)
        if (trim(strtolower($user->name)) !== trim(strtolower($request->name))) {
            RateLimiter::hit($throttleKey, 300);
            ActivityLog::record($user->id, 'embryologist_login_failed', null, [
                'reason' => 'name_mismatch',
            ]);
            return back()->withErrors([
                'auth_failed' => 'Authentication failed: Entered Full Name does not match laboratory record for this email.',
            ])->withInput($request->only('name', 'email'));
        }

        // Validate: Password is correct
        if (!Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 300);
            ActivityLog::record($user->id, 'embryologist_login_failed', null, [
                'reason' => 'invalid_password',
            ]);
            return back()->withErrors([
                'auth_failed' => 'Authentication failed: Invalid password.',
            ])->withInput($request->only('name', 'email'));
        }

        // Validate: Special Key matches account
        if (!$user->verifySpecialKey(trim($request->special_key))) {
            RateLimiter::hit($throttleKey, 300);
            ActivityLog::record($user->id, 'embryologist_login_failed', null, [
                'reason' => 'invalid_special_key',
            ]);
            return back()->withErrors([
                'auth_failed' => 'Authentication failed: Invalid or revoked Special Key. Contact your Administrator.',
            ])->withInput($request->only('name', 'email'));
        }

        // Validate: Account is active
        if (!$user->isActive()) {
            $statusMsg = $user->isSuspended()
                ? 'Your laboratory embryologist account has been suspended by the Administrator.'
                : 'Your laboratory embryologist account is inactive.';
            return back()->withErrors([
                'auth_failed' => $statusMsg,
            ])->withInput($request->only('name', 'email'));
        }

        RateLimiter::clear($throttleKey);

        // Step 3: All 4 credentials valid -> Dispatch 6-digit OTP
        $result = $this->otpService->generateAndSend($user->email, 'login', $user);

        if (!$result['success']) {
            return back()->withErrors(['auth_failed' => $result['message']])
                ->withInput($request->only('name', 'email'));
        }

        // Store pending session
        $request->session()->put('pending_auth', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => 'embryologist',
        ]);

        return redirect()->route('auth.otp.show')
            ->with('status', $result['message'])
            ->with('debug_otp', $result['debug_otp']);
    }
}

