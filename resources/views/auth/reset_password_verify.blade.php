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

