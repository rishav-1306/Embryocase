@extends('layouts.auth')

@section('title', 'OTP Verification')

@section('content')
<div class="auth-header">
    <div class="text-secondary-label mb-1">SECURITY VERIFICATION</div>
    <h1 style="font-size: 22px; margin-bottom: 4px;">Enter Verification Code</h1>
    <div class="text-meta" style="font-size: 13px;">
        A 6-digit security code has been transmitted to <strong class="text-dark">{{ $email }}</strong>.
    </div>
</div>

@if(session('debug_otp'))
    <div class="alert alert-dark p-3 mb-3 text-center mono" style="font-size: 13px; border: 1px dashed #000; background-color: var(--bg-off-white);">
        <span class="text-secondary-label d-block mb-1" style="font-size: 10px;">[LOCAL DEVELOPMENT MODE]</span>
        VERIFICATION CODE: <strong class="fs-4 text-black">{{ session('debug_otp') }}</strong>
        <div class="text-muted mt-1" style="font-size: 11px;">
            (Set up SMTP in <code>.env</code> to deliver directly to real inbox)
        </div>
    </div>
@elseif(config('mail.default') === 'log')
    <div class="alert alert-dark p-2 mb-3 text-center mono" style="font-size: 11px; border: 1px dashed var(--border-grey); background-color: var(--bg-off-white);">
        <span class="text-secondary-label d-block" style="font-size: 10px;">[MAIL DRIVER: LOG]</span>
        Emails are currently written to: <code class="mono">storage/logs/laravel.log</code>
    </div>
@endif

<form method="POST" action="{{ route('auth.otp.verify') }}" autocomplete="off">
    @csrf

    <div class="mb-4">
        <label for="otp" class="form-label text-center d-block">6-Digit Numerical Code</label>
        <input type="text"
               name="otp"
               id="otp"
               class="form-control text-center mono @error('otp') is-invalid @enderror"
               style="font-size: 28px; letter-spacing: 12px; font-weight: 700; height: 56px;"
               maxlength="6"
               pattern="\d{6}"
               inputmode="numeric"
               placeholder="000000"
               required
               autofocus>
        <div class="form-text text-center mt-2">
            Code valid for <strong>5 minutes</strong>. Maximum 5 verification attempts.
        </div>
        @error('otp')
            <div class="invalid-feedback text-center mt-2">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-sci-primary py-2">
            CONFIRM &amp; AUTHORIZE SESSION &rarr;
        </button>
    </div>
</form>

<form method="POST" action="{{ route('auth.otp.resend') }}" class="text-center pt-2 border-top" style="border-color: var(--border-grey) !important;">
    @csrf
    <div class="d-flex justify-content-between align-items-center" style="font-size: 12px;">
        <span class="text-muted">Didn't receive code?</span>
        <button type="submit" class="btn btn-sm btn-sci-secondary" id="resendBtn">
            Resend Verification Code
        </button>
    </div>
</form>

<div class="text-center mt-3 pt-2" style="font-size: 12px;">
    <a href="{{ $role === 'admin' ? route('admin.login') : route('login') }}" class="text-decoration-none text-muted">
        &larr; Cancel and return to login
    </a>
</div>
@endsection

