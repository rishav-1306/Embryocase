@extends('layouts.auth')

@section('title', 'Administrator Authentication')

@section('content')
<div class="auth-header">
    <div class="text-secondary-label mb-1">CENTRAL OWNERSHIP CONSOLE</div>
    <h1 style="font-size: 22px; margin-bottom: 4px;">Administrator Access</h1>
    <div class="text-meta" style="font-size: 13px;">
        Enter registered administrator email to dispatch verification OTP.
    </div>
</div>

<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 11px; letter-spacing: 0.5px;">
        <span class="text-uppercase fw-semibold text-dark">
            1. Admin Email
        </span>
        <span style="color: var(--border-grey);">&mdash;&mdash;</span>
        <span class="text-uppercase fw-semibold text-secondary">
            2. OTP Verification
        </span>
    </div>
    <div class="progress" style="height: 3px; background-color: var(--border-grey); border-radius: 0;">
        <div class="progress-bar bg-dark"
             role="progressbar"
             style="width: 50%;"
             aria-valuenow="1"
             aria-valuemin="1"
             aria-valuemax="2"></div>
    </div>
</div>

@if($errors->has('email'))
    <div class="alert-sci alert-sci-error mb-3">
        {{ $errors->first('email') }}
    </div>
@endif

<form method="POST" action="{{ route('admin.login.submit') }}" autocomplete="off">
    @csrf

    <div class="mb-4">
        <label for="email" class="form-label fw-semibold">Registered Administrator Email <span class="text-danger">*</span></label>
        <input type="email"
               name="email"
               id="email"
               class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email') }}"
               placeholder="rishav1306singh@gmail.com"
               required
               autofocus>
        <div class="form-text" style="font-size: 12px;">
            A one-time 6-digit OTP will be dispatched immediately to this address upon verification.
        </div>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-sci-primary py-2">
            VALIDATE EMAIL &amp; SEND OTP &rarr;
        </button>
    </div>

    <div class="d-flex justify-content-between align-items-center pt-2 border-top" style="border-color: var(--border-grey) !important; font-size: 12px;">
        <a href="{{ route('password.request') }}" class="text-decoration-none text-dark">
            Forgot Password?
        </a>
        <a href="{{ route('login') }}" class="text-decoration-none text-secondary">
            &larr; Embryologist Gateway
        </a>
    </div>
</form>
@endsection
