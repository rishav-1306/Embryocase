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
            background-color: #FFFFFF;
            background-image: url("{{ asset('images/auth-bg-embryo.svg') }}");
            background-repeat: no-repeat;
            background-position: center center;
            background-size: cover;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--text-black);
        }
        .sci-navbar {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }
        .auth-container {
            max-width: 440px;
            width: 100%;
            margin: auto;
            padding: 2.5rem 1.25rem;
        }
        .auth-card {
            border: 1px solid rgba(0, 0, 0, 0.07);
            border-radius: 16px;
            background-color: #FFFFFF;
            padding: 2.25rem 2.5rem;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.08), 0 3px 10px rgba(0, 0, 0, 0.03);
            transition: box-shadow 0.2s ease;
        }
        .auth-header {
            border-bottom: 1px solid var(--border-grey);
            padding-bottom: 1.25rem;
            margin-bottom: 1.75rem;
        }
        .auth-card .form-control {
            border-radius: 8px;
            border: 1px solid var(--border-grey);
            padding: 0.65rem 0.85rem;
            font-size: 14px;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .auth-card .form-control:focus {
            border-color: #000000;
            box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.08);
        }
        .auth-card .btn-sci-primary {
            border-radius: 8px;
            padding: 0.65rem 1rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
        }
        .auth-footer {
            margin-top: 1.75rem;
            text-align: center;
            font-size: 11px;
            letter-spacing: 0.6px;
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
            <div class="alert-sci alert-sci-success mb-3" style="border-radius: 8px;">
                {{ session('status') }}
            </div>
        @endif

        @if(session('debug_otp'))
            <div class="alert-sci mb-3" style="background-color: #FAFAFA; border: 1px dashed #404040; border-radius: 8px;">
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
