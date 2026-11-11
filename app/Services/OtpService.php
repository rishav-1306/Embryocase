<?php

namespace App\Services;

use App\Mail\OtpVerificationMail;
use App\Models\ActivityLog;
use App\Models\OtpVerification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    const EXPIRY_MINUTES = 5;
    const MAX_ATTEMPTS = 5;
    const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Generate and dispatch a 6-digit OTP
     *
     * @return array [success => bool, message => string, debug_otp => string|null]
     */
    public function generateAndSend(string $email, string $purpose = 'login', ?User $user = null): array
    {
        // Check resend cooldown
        $recentOtp = OtpVerification::where('email', $email)
            ->where('purpose', $purpose)
            ->where('created_at', '>', now()->subSeconds(self::RESEND_COOLDOWN_SECONDS))
            ->latest('id')
            ->first();

        if ($recentOtp) {
            $secondsRemaining = self::RESEND_COOLDOWN_SECONDS - now()->diffInSeconds($recentOtp->created_at);
            return [
                'success' => false,
                'message' => "Please wait {$secondsRemaining} seconds before requesting another verification code.",
                'debug_otp' => null,
            ];
        }

        // Invalidate any previous unverified OTPs for this email and purpose
        OtpVerification::where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->delete();

        // Generate 6-digit cryptographic code
        $code = sprintf("%06d", random_int(100000, 999999));

        $record = OtpVerification::create([
            'user_id' => $user ? $user->id : null,
            'email' => $email,
            'otp_hash' => Hash::make($code),
            'purpose' => $purpose,
            'attempts' => 0,
            'max_attempts' => self::MAX_ATTEMPTS,
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
            'ip_address' => request()->ip(),
        ]);

        // Send Email
        try {
            Mail::to($email)->send(new OtpVerificationMail($code, $purpose, self::EXPIRY_MINUTES));
        } catch (\Throwable $e) {
            Log::error("Failed to send OTP email: " . $e->getMessage());
        }

        ActivityLog::record($user?->id, 'otp_generated', null, [
            'email' => $email,
            'purpose' => $purpose,
        ]);

        return [
            'success' => true,
            'message' => "A 6-digit verification code has been dispatched to {$email}.",
            'debug_otp' => config('app.debug') ? $code : null,
        ];
    }

    /**
     * Verify an entered OTP
     *
     * @return array [success => bool, message => string, verification => OtpVerification|null]
     */
    public function verify(string $email, string $code, string $purpose = 'login'): array
    {
        $code = trim($code);

        $record = OtpVerification::where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (!$record) {
            return [
                'success' => false,
                'message' => 'No active verification code found. Please request a new code.',
                'verification' => null,
            ];
        }

        if ($record->isExpired()) {
            return [
                'success' => false,
                'message' => 'Verification code has expired. Please request a new code.',
                'verification' => null,
            ];
        }

        if ($record->hasExceededAttempts()) {
            return [
                'success' => false,
                'message' => 'Maximum verification attempts exceeded. Please request a new code.',
                'verification' => null,
            ];
        }

        // Increment attempt
        $record->increment('attempts');

        if (!$record->verifyCode($code)) {
            $remaining = $record->max_attempts - $record->attempts;
            return [
                'success' => false,
                'message' => "Invalid verification code. {$remaining} attempts remaining.",
                'verification' => null,
            ];
        }

        // Mark as verified
        $record->update([
            'verified_at' => now(),
        ]);

        ActivityLog::record($record->user_id, 'otp_verified', null, [
            'email' => $email,
            'purpose' => $purpose,
        ]);

        return [
            'success' => true,
            'message' => 'Verification successful.',
            'verification' => $record,
        ];
    }
}

