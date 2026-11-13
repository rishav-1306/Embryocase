@extends('layouts.auth')

@section('title', 'Embryologist Authentication')

@section('content')
<div class="auth-header">
    <div class="text-secondary-label mb-1">CLINICAL ACCESS PORTAL</div>
    <h1 style="font-size: 22px; margin-bottom: 4px;">Embryologist Authentication</h1>
    <div class="text-meta" style="font-size: 13px;">
        Laboratory account identity verification.
    </div>
</div>

@if($errors->has('auth_failed'))
    <div class="alert-sci alert-sci-error mb-3">
        {{ $errors->first('auth_failed') }}
    </div>
@endif

{{-- STEP 1: FULL NAME ONLY --}}
@if($step === 1)
<form method="POST" action="{{ route('login.embryologist.submit') }}" autocomplete="off">
    @csrf
    <input type="hidden" name="step" value="1">

    <div class="mb-4">
        <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
        <input type="text"
               name="name"
               id="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name') }}"
               placeholder="e.g. Aryan or Ayush"
               required
               autofocus>
        <div class="form-text" style="font-size: 12px;">
            Enter your registered laboratory full name.
        </div>
        @error('name')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-sci-primary py-2">
            CONTINUE &rarr;
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

{{-- STEP 2: SECRET KEY ONLY --}}
@elseif($step === 2)
<form method="POST" action="{{ route('login.embryologist.submit') }}" autocomplete="off">
    @csrf
    <input type="hidden" name="step" value="2">

    <div class="card p-2 mb-3 bg-light border" style="border-color: var(--border-grey) !important; font-size: 12px;">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <span class="text-secondary">Identity:</span>
                <strong class="text-dark ms-1">{{ $authenticatedName }}</strong>
            </div>
            <a href="{{ route('login.reset') }}" class="text-secondary text-decoration-none" style="font-size: 11px;">
                &larr; Change
            </a>
        </div>
    </div>

    <div class="mb-4">
        <label for="special_key" class="form-label fw-semibold">Secret Key <span class="text-danger">*</span></label>
        <input type="password"
               name="special_key"
               id="special_key"
               class="form-control mono @error('special_key') is-invalid @enderror"
               placeholder="Enter laboratory Secret Key"
               required
               autofocus>
        <div class="form-text" style="font-size: 12px;">
            Mandatory laboratory secret code issued by your Administrator.
        </div>
        @error('special_key')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-sci-primary py-2">
            CONTINUE &rarr;
        </button>
    </div>

    <div class="d-flex justify-content-between align-items-center pt-2 border-top" style="border-color: var(--border-grey) !important; font-size: 12px;">
        <a href="{{ route('login.reset') }}" class="text-decoration-none text-secondary">
            &larr; Start Over
        </a>
        <a href="{{ route('admin.login') }}" class="text-decoration-none text-secondary">
            Administrator Gateway &rarr;
        </a>
    </div>
</form>

{{-- STEP 3: EMAIL ONLY --}}
@elseif($step === 3)
<form method="POST" action="{{ route('login.embryologist.submit') }}" autocomplete="off">
    @csrf
    <input type="hidden" name="step" value="3">

    <div class="card p-2 mb-3 bg-light border" style="border-color: var(--border-grey) !important; font-size: 12px;">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <span class="text-secondary">Identity:</span>
                <strong class="text-dark ms-1">{{ $authenticatedName }}</strong>
            </div>
            <a href="{{ route('login.reset') }}" class="text-secondary text-decoration-none" style="font-size: 11px;">
                &larr; Start Over
            </a>
        </div>
    </div>

    <div class="mb-4">
        <label for="email" class="form-label fw-semibold">Registered Email Address <span class="text-danger">*</span></label>
        <input type="email"
               name="email"
               id="email"
               class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email') }}"
               placeholder="e.g. nikhilaryan0223@gmail.com"
               required
               autofocus>
        <div class="form-text" style="font-size: 12px;">
            Enter your registered laboratory email address.
        </div>
        @error('email')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-grid mb-3">
        <button type="submit" class="btn btn-sci-primary py-2">
            CONTINUE &rarr;
        </button>
    </div>

    <div class="d-flex justify-content-between align-items-center pt-2 border-top" style="border-color: var(--border-grey) !important; font-size: 12px;">
        <a href="{{ route('login.reset') }}" class="text-decoration-none text-secondary">
            &larr; Start Over
        </a>
        <a href="{{ route('admin.login') }}" class="text-decoration-none text-secondary">
            Administrator Gateway &rarr;
        </a>
    </div>
</form>
@endif

@endsection
