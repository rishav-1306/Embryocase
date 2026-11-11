<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ForgotPasswordController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function showRequestForm()
    {
        return view('auth.forgot_password');
    }

    public function sendResetOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = $request->email;
        $user = User::where('email', $email)->first();

        // Always show generic message to avoid user enumeration
        $genericMessage = 'If your email address is registered in our laboratory records, a 6-digit verification code has been dispatched.';

        if ($user) {
            $result = $this->otpService->generateAndSend($email, 'password_reset', $user);

            // Store email in session for the reset workflow
            $request->session()->put('password_reset_email', $email);

            return redirect()->route('password.verify.show')
                ->with('status', $genericMessage)
                ->with('debug_otp', $result['debug_otp'] ?? null);
        }

        // Even if user does not exist, pretend it sent
        $request->session()->put('password_reset_email', $email);
        return redirect()->route('password.verify.show')->with('status', $genericMessage);
    }

    public function showVerifyForm(Request $request)
    {
        $email = $request->session()->get('password_reset_email');
        if (!$email) {
            return redirect()->route('password.request');
        }

        return view('auth.reset_password_verify', ['email' => $email]);
    }

    public function verifyResetOtp(Request $request)
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $email = $request->session()->get('password_reset_email');
        if (!$email) {
            return redirect()->route('password.request');
        }

        $result = $this->otpService->verify($email, $request->otp, 'password_reset');

        if (!$result['success']) {
            return back()->withErrors(['otp' => $result['message']]);
        }

        // OTP verified successfully; allow setting new password
        $request->session()->put('password_reset_verified_email', $email);

        return redirect()->route('password.reset.new.show');
    }

    public function showNewPasswordForm(Request $request)
    {
        $email = $request->session()->get('password_reset_verified_email');
        if (!$email) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'Please verify your OTP before resetting your password.',
            ]);
        }

        return view('auth.reset_password_new', ['email' => $email]);
    }

    public function updatePassword(Request $request)
    {
        $email = $request->session()->get('password_reset_verified_email');
        if (!$email) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'Unauthorized password reset attempt.',
            ]);
        }

        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The password must be at least 8 characters and contain letters and numbers.',
        ]);

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            // Invalidate other sessions
            DB::table('sessions')->where('user_id', $user->id)->delete();

            ActivityLog::record($user->id, 'password_reset_completed', null, [
                'email' => $email,
            ]);
        }

        $request->session()->forget(['password_reset_email', 'password_reset_verified_email']);

        return redirect()->route('login')->with('status', 'Your laboratory password has been successfully reset. Please log in.');
    }
}

