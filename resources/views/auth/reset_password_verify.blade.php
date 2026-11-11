@extends('layouts.auth')

@section('title', 'Verify Recovery Code')

@section('content')
<div class="auth-header">
    <div class="text-secondary-label mb-1">ACCOUNT RECOVERY &bull; STEP 2</div>
    <h1 style="font-size: 22px; margin-bottom: 4px;">Enter Recovery OTP</h1>
    <div class="text-meta" style="font-size: 13px;">
        A 6-digit password reset code was sent to <strong class="text-dark">{{ $email }}</strong>.
    </div>
</div>

@if(session('debug_otp'))
    <div class="alert alert-dark p-3 mb-3 text-center mono" style="font-size: 13px; border: 1px dashed #000; background-color: var(--bg-off-white);">
        <span class="text-secondary-label d-block mb-1" style="font-size: 10px;">[LOCAL DEVELOPMENT MODE]</span>
        RECOVERY CODE: <strong class="fs-4 text-black">{{ session('debug_otp') }}</strong>
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

<form method="POST" action="{{ route('password.verify.submit') }}" autocomplete="off">
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
        @error('otp')
            <div class="invalid-feedback text-center mt-2">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-sci-primary py-2">
            VERIFY CODE &amp; CONTINUE &rarr;
        </button>
    </div>

    <div class="text-center pt-2 border-top" style="border-color: var(--border-grey) !important; font-size: 12px;">
        <a href="{{ route('password.request') }}" class="text-decoration-none text-muted">
            &larr; Request a different code
        </a>
    </div>
</form>
@endsection

