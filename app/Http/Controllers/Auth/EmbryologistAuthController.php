<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class EmbryologistAuthController extends Controller
{
    protected OtpService $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            return Auth::user()->isAdmin()
                ? redirect()->route('admin.dashboard')
                : redirect()->route('embryologist.dashboard');
        }

        $step = (int) $request->session()->get('embryo_auth_step', 1);
        $authenticatedName = $request->session()->get('embryo_auth_name', '');

        // Safety fallback: if step > 1 but session data missing, reset to step 1
        if ($step === 2 && empty($authenticatedName)) {
            $step = 1;
            $request->session()->put('embryo_auth_step', 1);
        } elseif ($step === 3 && (!$request->session()->has('embryo_auth_user_id') || empty($authenticatedName))) {
            $step = 1;
            $request->session()->put('embryo_auth_step', 1);
        }

        return view('auth.embryologist_login', [
            'step' => $step,
            'authenticatedName' => $authenticatedName,
        ]);
    }

    public function resetLogin(Request $request)
    {
        $request->session()->forget([
            'embryo_auth_step',
            'embryo_auth_name',
            'embryo_auth_user_id',
            'embryo_auth_candidates',
        ]);

        return redirect()->route('login');
    }

    public function submitCredentials(Request $request)
    {
        // Support legacy all-in-one submission if all credentials are sent simultaneously without step
        if (!$request->has('step') && $request->filled('name') && $request->filled('special_key') && $request->filled('email')) {
            return $this->handleAllInOneSubmission($request);
        }

        $step = (int) $request->input('step', $request->session()->get('embryo_auth_step', 1));

        if ($step === 1) {
            return $this->handleStepOne($request);
        } elseif ($step === 2) {
            return $this->handleStepTwo($request);
        } elseif ($step === 3) {
            return $this->handleStepThree($request);
        }

        return redirect()->route('login.reset');
    }

    /**
     * Step 1: Validate Name matches active embryologist records.
     */
    protected function handleStepOne(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], [
            'name.required' => 'Please enter your Full Name to proceed.',
        ]);

        $throttleKey = 'embryo_login_step1|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'auth_failed' => "Too many attempts. Access temporarily locked for {$seconds} seconds.",
            ]);
        }

        $enteredName = trim($request->name);

        $matchedUsers = User::where('role', 'embryologist')
            ->where('status', 'active')
            ->get()
            ->filter(function ($user) use ($enteredName) {
                return trim(strtolower($user->name)) === strtolower($enteredName);
            });

        if ($matchedUsers->isEmpty()) {
            RateLimiter::hit($throttleKey, 300);
            ActivityLog::record(null, 'embryologist_step1_failed', null, [
                'name' => $enteredName,
                'reason' => 'name_not_found',
                'ip' => $request->ip(),
            ]);

            return back()->withErrors([
                'name' => "No active embryologist account matches the name '{$enteredName}'. Please verify spelling.",
            ])->withInput();
        }

        RateLimiter::clear($throttleKey);

        $matchedUser = $matchedUsers->first();
        $request->session()->put('embryo_auth_step', 2);
        $request->session()->put('embryo_auth_name', $matchedUser->name);
        $request->session()->put('embryo_auth_candidates', $matchedUsers->pluck('id')->toArray());

        return redirect()->route('login');
    }

    /**
     * Step 2: Validate Secret Key matches for the authenticated name.
     */
    protected function handleStepTwo(Request $request)
    {
        $request->validate([
            'special_key' => ['required', 'string'],
        ], [
            'special_key.required' => 'Please enter your unique Secret Key.',
        ]);

        $name = $request->session()->get('embryo_auth_name');
        $candidateIds = $request->session()->get('embryo_auth_candidates', []);

        if (empty($name) || empty($candidateIds)) {
            return redirect()->route('login.reset')->withErrors([
                'auth_failed' => 'Authentication session expired. Please start again from Step 1.',
            ]);
        }

        $throttleKey = 'embryo_login_step2|' . $request->ip() . '|' . $name;
        if (RateLimiter::tooManyAttempts($throttleKey, 7)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'auth_failed' => "Too many invalid key attempts. Locked for {$seconds} seconds.",
            ]);
        }

        $candidates = User::whereIn('id', $candidateIds)->where('status', 'active')->get();
        $matchedUser = null;

        foreach ($candidates as $candidate) {
            if ($candidate->verifySpecialKey(trim($request->special_key))) {
                $matchedUser = $candidate;
                break;
            }
        }

        if (!$matchedUser) {
            RateLimiter::hit($throttleKey, 300);
            ActivityLog::record(null, 'embryologist_step2_failed', null, [
                'name' => $name,
                'reason' => 'invalid_secret_key',
                'ip' => $request->ip(),
            ]);

            return back()->withErrors([
                'special_key' => "Invalid Secret Key for {$name}. Contact your Administrator if key was regenerated.",
            ]);
        }

        RateLimiter::clear($throttleKey);

        $request->session()->put('embryo_auth_step', 3);
        $request->session()->put('embryo_auth_user_id', $matchedUser->id);

        return redirect()->route('login');
    }

    /**
     * Step 3: Validate Email matches the account, then dispatch OTP.
     */
    protected function handleStepThree(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ], [
            'email.required' => 'Please enter your registered email address.',
        ]);

        $name = $request->session()->get('embryo_auth_name');
        $userId = $request->session()->get('embryo_auth_user_id');

        if (empty($userId)) {
            return redirect()->route('login.reset')->withErrors([
                'auth_failed' => 'Authentication session expired. Please start again from Step 1.',
            ]);
        }

        $user = User::find($userId);
        if (!$user || !$user->isActive()) {
            return redirect()->route('login.reset')->withErrors([
                'auth_failed' => 'Embryologist account is inactive or not found.',
            ]);
        }

        $throttleKey = 'embryo_login_step3|' . $request->ip() . '|' . $userId;
        if (RateLimiter::tooManyAttempts($throttleKey, 7)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'auth_failed' => "Too many attempts. Locked for {$seconds} seconds.",
            ]);
        }

        if (trim(strtolower($request->email)) !== trim(strtolower($user->email))) {
            RateLimiter::hit($throttleKey, 300);
            ActivityLog::record($user->id, 'embryologist_step3_failed', null, [
                'entered_email' => $request->email,
                'reason' => 'email_mismatch',
                'ip' => $request->ip(),
            ]);

            return back()->withErrors([
                'email' => "Entered email does not match laboratory records for {$name}.",
            ])->withInput();
        }

        RateLimiter::clear($throttleKey);

        // Step 3 matched! Dispatch 6-digit OTP
        $result = $this->otpService->generateAndSend($user->email, 'login', $user);

        if (!$result['success']) {
            return back()->withErrors(['email' => $result['message']]);
        }

        // Clean up step session
        $request->session()->forget([
            'embryo_auth_step',
            'embryo_auth_name',
            'embryo_auth_user_id',
            'embryo_auth_candidates',
        ]);

        // Store pending auth
        $request->session()->put('pending_auth', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => 'embryologist',
        ]);

        return redirect()->route('auth.otp.show')
            ->with('status', $result['message'])
            ->with('debug_otp', $result['debug_otp']);
    }

    /**
     * Fallback for direct all-in-one submissions (e.g. legacy/automated calls).
     */
    protected function handleAllInOneSubmission(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'special_key' => ['required', 'string'],
        ]);

        $user = User::where('role', 'embryologist')
            ->where('email', $request->email)
            ->first();

        if (!$user || trim(strtolower($user->name)) !== trim(strtolower($request->name)) || !$user->verifySpecialKey(trim($request->special_key)) || !$user->isActive()) {
            return back()->withErrors([
                'auth_failed' => 'Authentication failed. Please verify all laboratory credentials.',
            ]);
        }

        $result = $this->otpService->generateAndSend($user->email, 'login', $user);
        if (!$result['success']) {
            return back()->withErrors(['auth_failed' => $result['message']]);
        }

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
