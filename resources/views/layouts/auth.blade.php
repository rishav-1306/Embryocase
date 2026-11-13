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
            background-color: #FAFAFA;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow-x: hidden;
        }

        /* Fixed Blurry Black Background Decor */
        .auth-bg-decor {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        .auth-blur-shape {
            position: absolute;
            border-radius: 50%;
            background-color: #000000;
            pointer-events: none;
            will-change: transform, opacity, filter;
        }

        /* Ambient Blob 1 (Top-Right) */
        .auth-blur-1 {
            width: 520px;
            height: 520px;
            top: -140px;
            right: -120px;
            opacity: 0.08;
            filter: blur(100px);
            animation: quickReloadBlur1 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Ambient Blob 2 (Bottom-Left) */
        .auth-blur-2 {
            width: 580px;
            height: 580px;
            bottom: -180px;
            left: -140px;
            opacity: 0.07;
            filter: blur(110px);
            animation: quickReloadBlur2 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Ambient Focal Vignette (Center Behind Form) */
        .auth-blur-3 {
            width: 420px;
            height: 420px;
            top: 48%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.05;
            filter: blur(95px);
            animation: quickReloadBlurCenter 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Quick reload animation: rapidly reveals on load/reload then remains completely stable */
        @keyframes quickReloadBlur1 {
            0% {
                opacity: 0;
                transform: scale(0.85);
                filter: blur(40px);
            }
            100% {
                opacity: 0.08;
                transform: scale(1);
                filter: blur(100px);
            }
        }

        @keyframes quickReloadBlur2 {
            0% {
                opacity: 0;
                transform: scale(0.85);
                filter: blur(40px);
            }
            100% {
                opacity: 0.07;
                transform: scale(1);
                filter: blur(110px);
            }
        }

        @keyframes quickReloadBlurCenter {
            0% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.85);
                filter: blur(40px);
            }
            100% {
                opacity: 0.05;
                transform: translate(-50%, -50%) scale(1);
                filter: blur(95px);
            }
        }

        .sci-navbar {
            position: relative;
            z-index: 2;
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
        }

        .auth-container {
            position: relative;
            z-index: 1;
            max-width: 480px;
            width: 100%;
            margin: auto;
            padding: 2.5rem 1rem;
            animation: authQuickIn 0.35s ease-out forwards;
        }

        @keyframes authQuickIn {
            0% {
                opacity: 0;
                transform: translateY(6px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-card {
            border: 1px solid var(--border-grey);
            background-color: var(--bg-white);
            padding: 2rem 2.25rem;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.04);
            position: relative;
            z-index: 2;
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
            position: relative;
            z-index: 2;
        }
    </style>
</head>
<body>
    {{-- Stable Blurry Black Background Decor with Quick Reload Reveal Animation --}}
    <div class="auth-bg-decor" aria-hidden="true">
        <div class="auth-blur-shape auth-blur-1"></div>
        <div class="auth-blur-shape auth-blur-2"></div>
        <div class="auth-blur-shape auth-blur-3"></div>
    </div>

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
