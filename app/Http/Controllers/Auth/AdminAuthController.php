<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AdminAuthController extends Controller
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

        return view('auth.admin_login');
    }

    public function submitCredentials(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ], [
            'email.required' => 'Please enter the registered administrator email address.',
            'email.email' => 'Please enter a valid email address.',
        ]);

        $throttleKey = 'admin_login|' . $request->ip() . '|' . trim(strtolower($request->email));

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => "Too many attempts. Access temporarily locked for {$seconds} seconds.",
            ]);
        }

        $admin = User::where('role', 'admin')
            ->get()
            ->first(function ($user) use ($request) {
                return trim(strtolower($user->email)) === trim(strtolower($request->email));
            });

        if (!$admin) {
            RateLimiter::hit($throttleKey, 300);

            ActivityLog::record(null, 'admin_login_failed', null, [
                'email' => $request->email,
                'reason' => 'email_not_found',
                'ip' => $request->ip(),
            ]);

            return back()->withErrors([
                'email' => 'The provided administrator email does not match our records.',
            ])->withInput($request->only('email'));
        }

        if (!$admin->isActive()) {
            return back()->withErrors([
                'email' => 'This administrator account is currently deactivated or suspended.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        // Dispatch 6-Digit OTP to Admin Email
        $result = $this->otpService->generateAndSend($admin->email, 'login', $admin);

        if (!$result['success']) {
            return back()->withErrors(['email' => $result['message']]);
        }

        // Store pending credentials in session
        $request->session()->put('pending_auth', [
            'user_id' => $admin->id,
            'email' => $admin->email,
            'role' => 'admin',
        ]);

        return redirect()->route('auth.otp.show')->with('status', $result['message'])
            ->with('debug_otp', $result['debug_otp']);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            ActivityLog::record($user->id, 'logout', null);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been successfully logged out.');
    }
}
