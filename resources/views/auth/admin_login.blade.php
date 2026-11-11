@extends('layouts.auth')

@section('title', 'Administrator Authentication')

@section('content')
<div class="auth-header">
    <div class="text-secondary-label mb-1">CENTRAL OWNERSHIP CONSOLE</div>
    <h1 style="font-size: 22px; margin-bottom: 4px;">Administrator Access</h1>
    <div class="text-meta" style="font-size: 13px;">
        Central administrative credentials for laboratory repository ownership.
    </div>
</div>

<form method="POST" action="{{ route('admin.login.submit') }}" autocomplete="off">
    @csrf

    <div class="mb-3">
        <label for="email" class="form-label">Registered Administrator Email <span class="text-danger">*</span></label>
        <input type="email"
               name="email"
               id="email"
               class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email') }}"
               placeholder="admin@embryocase.lab"
               required
               autofocus>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="password" class="form-label">Administrator Password <span class="text-danger">*</span></label>
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

    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-sci-primary py-2">
            VALIDATE &amp; SEND OTP &rarr;
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
