@extends('layouts.app')

@section('title', 'Provision Embryologist')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom" style="border-color: var(--border-grey) !important;">
    <div>
        <div class="text-secondary-label">STAFF ACCESS PROVISIONING</div>
        <h1 class="h2 mb-0">Create Embryologist Account</h1>
    </div>
    <div>
        <a href="{{ route('admin.embryologists.index') }}" class="btn btn-sci-secondary">
            &larr; BACK TO LIST
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="sci-card">
            <div class="sci-card-header">
                <span class="sci-card-title">ACCOUNT &amp; AUTHENTICATION CREDENTIALS</span>
            </div>
            <div class="sci-card-body p-4">
                <form method="POST" action="{{ route('admin.embryologists.store') }}" autocomplete="off">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Embryologist Full Name <span class="text-danger">*</span></label>
                        <input type="text"
                               name="name"
                               id="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}"
                               placeholder="e.g. Dr. Arthur Miller"
                               required
                               autofocus>
                        <div class="form-text">
                            Must match what the embryologist enters upon login.
                        </div>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Registered Laboratory Email Address <span class="text-danger">*</span></label>
                        <input type="email"
                               name="email"
                               id="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}"
                               placeholder="arthur.miller@embryocase.lab"
                               required>
                        <div class="form-text">
                            Used for credentials verification and login OTP transmission.
                        </div>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Initial Password <span class="text-danger">*</span></label>
                        <input type="password"
                               name="password"
                               id="password"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="Minimum 8 characters"
                               required>
                        <div class="form-text">
                            Temporary password for initial login. Embryologist can reset via OTP recovery.
                        </div>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="alert-sci mb-4" style="background-color: var(--bg-off-white); border-left: 3px solid var(--text-black);">
                        <strong>Automated Special Key Generation:</strong><br>
                        Upon saving, the backend will automatically generate a cryptographically unique Special Key (e.g. <code>EMB-XXXX-XXXX</code>) and hash it in MySQL. The plaintext key will be presented securely once to you on the confirmation screen for direct physical or encrypted distribution to the embryologist.
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top" style="border-color: var(--border-grey) !important;">
                        <a href="{{ route('admin.embryologists.index') }}" class="btn btn-sci-secondary">CANCEL</a>
                        <button type="submit" class="btn btn-sci-primary px-4">
                            GENERATE ACCOUNT &amp; KEY &rarr;
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

