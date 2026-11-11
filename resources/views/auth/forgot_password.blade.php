@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
<div class="auth-header">
    <div class="text-secondary-label mb-1">ACCOUNT RECOVERY</div>
    <h1 style="font-size: 22px; margin-bottom: 4px;">Reset Password</h1>
    <div class="text-meta" style="font-size: 13px;">
        Enter your registered email address to receive a 6-digit OTP verification code.
    </div>
</div>

<form method="POST" action="{{ route('password.email') }}" autocomplete="off">
    @csrf

    <div class="mb-4">
        <label for="email" class="form-label">Registered Email Address <span class="text-danger">*</span></label>
        <input type="email"
               name="email"
               id="email"
               class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email') }}"
               placeholder="name@embryocase.lab"
               required
               autofocus>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-sci-primary py-2">
            DISPATCH RECOVERY CODE &rarr;
        </button>
    </div>

    <div class="text-center pt-2 border-top" style="border-color: var(--border-grey) !important; font-size: 12px;">
        <a href="{{ route('login') }}" class="text-decoration-none text-muted">
            &larr; Back to Login
        </a>
    </div>
</form>
@endsection

