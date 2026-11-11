@extends('layouts.auth')

@section('title', 'Set New Password')

@section('content')
<div class="auth-header">
    <div class="text-secondary-label mb-1">ACCOUNT RECOVERY &bull; FINAL STEP</div>
    <h1 style="font-size: 22px; margin-bottom: 4px;">Set New Password</h1>
    <div class="text-meta" style="font-size: 13px;">
        Define a secure laboratory password for <strong class="text-dark">{{ $email }}</strong>.
    </div>
</div>

<form method="POST" action="{{ route('password.reset.new.submit') }}" autocomplete="off">
    @csrf

    <div class="mb-3">
        <label for="password" class="form-label">New Password <span class="text-danger">*</span></label>
        <input type="password"
               name="password"
               id="password"
               class="form-control @error('password') is-invalid @enderror"
               placeholder="Minimum 8 characters (letters &amp; numbers)"
               required
               autofocus>
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="password_confirmation" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
        <input type="password"
               name="password_confirmation"
               id="password_confirmation"
               class="form-control"
               placeholder="Re-enter new password"
               required>
    </div>

    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-sci-primary py-2">
            UPDATE PASSWORD &amp; RETURN TO LOGIN &rarr;
        </button>
    </div>
</form>
@endsection
