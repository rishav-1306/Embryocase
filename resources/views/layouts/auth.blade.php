<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Authentication') — EMBRYOCASE</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/embryocase.css') }}">
    <style>
        body {
            background-color: var(--bg-white);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .auth-container {
            max-width: 480px;
            width: 100%;
            margin: auto;
            padding: 2.5rem 1rem;
        }
        .auth-card {
            border: 1px solid var(--border-grey);
            background-color: var(--bg-white);
            padding: 2rem 2.25rem;
        }
        .auth-header {
            border-bottom: 1px solid var(--border-grey);
            padding-bottom: 1.25rem;
            margin-bottom: 1.75rem;
        }
        .auth-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 12px;
            color: var(--text-medium-grey);
        }
    </style>
</head>
<body>
    <div class="sci-navbar">
        <a href="/" class="sci-brand">
            <div class="sci-brand-logo">E</div>
            <div>
                <span class="sci-brand-name">EMBRYOCASE</span>
                <span class="sci-brand-tagline">Embryo Data &amp; Research Management System</span>
            </div>
        </a>
        <div class="text-secondary-label">
            LABORATORY GATEWAY
        </div>
    </div>

    <div class="auth-container">
        @if(session('status'))
            <div class="alert-sci alert-sci-success mb-3">
                {{ session('status') }}
            </div>
        @endif

        @if(session('debug_otp'))
            <div class="alert-sci mb-3" style="background-color: #FAFAFA; border: 1px dashed #404040;">
                <span class="text-secondary-label">[Local Testing Dispatch Code]:</span>
                <strong class="mono" style="font-size: 16px; margin-left: 8px; letter-spacing: 2px;">{{ session('debug_otp') }}</strong>
            </div>
        @endif

        <div class="auth-card">
            @yield('content')
        </div>

        <div class="auth-footer">
            SECURE LABORATORY PLATFORM &bull; ACCESS STRICTLY AUDITED &bull; EMBRYOCASE v1.0
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    @yield('scripts')
</body>
</html>

