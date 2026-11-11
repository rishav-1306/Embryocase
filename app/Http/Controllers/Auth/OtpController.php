<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OtpController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function showVerifyForm(Request $request)
    {
        $pending = $request->session()->get('pending_auth');

        if (!$pending) {
            return redirect()->route('login')->withErrors([
                'auth_failed' => 'Session expired or invalid login attempt. Please authenticate again.',
            ]);
        }

        return view('auth.otp_verify', [
            'email' => $pending['email'],
            'role' => $pending['role'],
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ], [
            'otp.required' => 'The 6-digit verification code is required.',
            'otp.size' => 'The verification code must be exactly 6 digits.',
        ]);

        $pending = $request->session()->get('pending_auth');

        if (!$pending) {
            return redirect()->route('login')->withErrors([
                'auth_failed' => 'Verification session expired. Please log in again.',
            ]);
        }

        $result = $this->otpService->verify($pending['email'], $request->otp, 'login');

        if (!$result['success']) {
            return back()->withErrors(['otp' => $result['message']]);
        }

        $user = User::find($pending['user_id']);

        if (!$user || !$user->isActive()) {
            $request->session()->forget('pending_auth');
            return redirect()->route('login')->withErrors([
                'auth_failed' => 'User account is not active.',
            ]);
        }

        // Complete authentication
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('pending_auth');

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        ActivityLog::record($user->id, 'login_success', null, [
            'ip' => $request->ip(),
            'role' => $user->role,
        ]);

        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('embryologist.dashboard'));
    }

    public function resendOtp(Request $request)
    {
        $pending = $request->session()->get('pending_auth');

        if (!$pending) {
            return redirect()->route('login')->withErrors([
                'auth_failed' => 'Verification session expired.',
            ]);
        }

        $user = User::find($pending['user_id']);
        $result = $this->otpService->generateAndSend($pending['email'], 'login', $user);

        if (!$result['success']) {
            return back()->withErrors(['otp' => $result['message']]);
        }

        return back()->with('status', $result['message'])
            ->with('debug_otp', $result['debug_otp']);
    }
}

