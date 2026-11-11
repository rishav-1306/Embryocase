@extends('layouts.auth')

@section('title', 'Embryologist Authentication')

@section('content')
<div class="auth-header">
    <div class="text-secondary-label mb-1">CLINICAL ACCESS PORTAL</div>
    <h1 style="font-size: 22px; margin-bottom: 4px;">Embryologist Authentication</h1>
    <div class="text-meta" style="font-size: 13px;">
        Four mandatory laboratory credentials required for session authorization.
    </div>
</div>

@if($errors->has('auth_failed'))
    <div class="alert-sci alert-sci-error mb-3">
        {{ $errors->first('auth_failed') }}
    </div>
@endif

<form method="POST" action="{{ route('login.embryologist.submit') }}" autocomplete="off">
    @csrf

    <div class="mb-3">
        <label for="name" class="form-label">1. Full Name <span class="text-danger">*</span></label>
        <input type="text"
               name="name"
               id="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name') }}"
               placeholder="e.g. Dr. Sarah Chen"
               required
               autofocus>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">2. Registered Email Address <span class="text-danger">*</span></label>
        <input type="email"
               name="email"
               id="email"
               class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email') }}"
               placeholder="name@embryocase.lab"
               required>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">3. Password <span class="text-danger">*</span></label>
        <input type="password"
               name="password"
               id="password"
               class="form-control @error('password') is-invalid @enderror"
               placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
               required>
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="special_key" class="form-label">4. Unique Special Key <span class="text-danger">*</span></label>
        <input type="text"
               name="special_key"
               id="special_key"
               class="form-control mono @error('special_key') is-invalid @enderror"
               placeholder="EMB-KEY-XXXX-XXXX"
               required>
        <div class="form-text">
            Mandatory laboratory key issued and managed by your Administrator.
        </div>
        @error('special_key')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-sci-primary py-2">
            PROCEED TO OTP VERIFICATION &rarr;
        </button>
    </div>

    <div class="d-flex justify-content-between align-items-center pt-2 border-top" style="border-color: var(--border-grey) !important; font-size: 12px;">
        <a href="{{ route('password.request') }}" class="text-decoration-none text-dark">
            Forgot Password?
        </a>
        <a href="{{ route('admin.login') }}" class="text-decoration-none text-secondary">
            Administrator Gateway &rarr;
        </a>
    </div>
</form>
@endsection

