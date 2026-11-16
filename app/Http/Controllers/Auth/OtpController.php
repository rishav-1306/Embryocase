<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\Google2FAService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OtpController extends Controller
{
    protected Google2FAService $google2FAService;

    public function __construct(Google2FAService $google2FAService)
    {
        $this->google2FAService = $google2FAService;
    }

    public function showVerifyForm(Request $request)
    {
        $pending = $request->session()->get('pending_auth');

        if (!$pending) {
            return redirect()->route('login')->withErrors([
                'auth_failed' => 'Session expired or invalid login attempt. Please authenticate again.',
            ]);
        }

        $user = User::find($pending['user_id']);
        if (!$user || !$user->isActive()) {
            $request->session()->forget('pending_auth');
            return redirect()->route('login')->withErrors([
                'auth_failed' => 'User account is not active.',
            ]);
        }

        $secret = $this->google2FAService->getOrCreateSecret($user);
        $otpauthUri = $this->google2FAService->getQrCodeUri($user->email, $secret);
        $qrCode = $this->google2FAService->getQrCodeDataUri($otpauthUri);

        $isFirstSetup = !$user->google2fa_enabled;
        $debugOtp = config('app.debug') ? $this->google2FAService->getCurrentOtp($secret) : null;

        return view('auth.otp_verify', [
            'email' => $user->email,
            'role' => $user->role,
            'secret' => $secret,
            'qrCode' => $qrCode,
            'isFirstSetup' => $isFirstSetup,
            'debugOtp' => $debugOtp,
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ], [
            'otp.required' => 'The 6-digit Google Authenticator verification code is required.',
            'otp.size' => 'The verification code must be exactly 6 digits.',
        ]);

        $pending = $request->session()->get('pending_auth');

        if (!$pending) {
            return redirect()->route('login')->withErrors([
                'auth_failed' => 'Verification session expired. Please log in again.',
            ]);
        }

        $user = User::find($pending['user_id']);

        if (!$user || !$user->isActive()) {
            $request->session()->forget('pending_auth');
            return redirect()->route('login')->withErrors([
                'auth_failed' => 'User account is not active.',
            ]);
        }

        $secret = $this->google2FAService->getOrCreateSecret($user);
        $isValid = $this->google2FAService->verifyKey($secret, $request->otp);

        if (!$isValid) {
            ActivityLog::record($user->id, 'google2fa_failed', null, [
                'ip' => $request->ip(),
                'role' => $user->role,
            ]);

            return back()->withErrors(['otp' => 'Invalid Google Authenticator code. Please check your app and try again.']);
        }

        if (!$user->google2fa_enabled) {
            $user->google2fa_enabled = true;
            $user->save();
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
            'method' => 'google_authenticator',
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
            return redirect()->route('login');
        }

        $user = User::find($pending['user_id']);
        if ($user) {
            $user->google2fa_secret = $this->google2FAService->generateSecretKey(16);
            $user->google2fa_enabled = false;
            $user->save();
        }

        return redirect()->route('auth.otp.show')->with('status', 'A new Google Authenticator key and QR code have been generated.');
    }
}
