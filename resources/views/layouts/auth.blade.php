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
            background-color: #ECEEF0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow-x: hidden;
        }

        /* Fixed Blurry Black Background Decor - 80% Visibility */
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
            pointer-events: none;
            will-change: transform, opacity, filter;
        }

        /* Shape 1: Top-Right Bold Black Cloud (80% visible) */
        .auth-blur-1 {
            width: 650px;
            height: 650px;
            top: -180px;
            right: -140px;
            background: radial-gradient(circle, rgba(0, 0, 0, 0.85) 0%, rgba(15, 15, 15, 0.75) 45%, rgba(30, 30, 30, 0.35) 70%, transparent 100%);
            filter: blur(50px);
            opacity: 0.80;
            animation: quickReloadBloom1 0.55s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Shape 2: Bottom-Left Bold Black Cloud (80% visible) */
        .auth-blur-2 {
            width: 680px;
            height: 680px;
            bottom: -200px;
            left: -160px;
            background: radial-gradient(circle, rgba(0, 0, 0, 0.85) 0%, rgba(15, 15, 15, 0.72) 45%, rgba(30, 30, 30, 0.35) 70%, transparent 100%);
            filter: blur(55px);
            opacity: 0.80;
            animation: quickReloadBloom2 0.55s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Shape 3: Central Backing Cloud framing the login card (80% visible core) */
        .auth-blur-3 {
            width: 580px;
            height: 580px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: radial-gradient(circle, rgba(0, 0, 0, 0.82) 0%, rgba(20, 20, 20, 0.65) 45%, rgba(40, 40, 40, 0.25) 75%, transparent 100%);
            filter: blur(48px);
            opacity: 0.80;
            animation: quickReloadBloomCenter 0.55s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Shape 4: Top-Left Balancing Cloud */
        .auth-blur-4 {
            width: 440px;
            height: 440px;
            top: 10%;
            left: -120px;
            background: radial-gradient(circle, rgba(0, 0, 0, 0.80) 0%, rgba(25, 25, 25, 0.60) 50%, transparent 80%);
            filter: blur(45px);
            opacity: 0.75;
            animation: quickReloadBloom4 0.55s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Quick reload animation: punchy fast bloom to 80% visibility on load/reload, then completely static */
        @keyframes quickReloadBloom1 {
            0% {
                opacity: 0;
                transform: scale(0.6) translate(40px, -40px);
                filter: blur(15px);
            }
            70% {
                opacity: 0.90;
                transform: scale(1.04) translate(0, 0);
                filter: blur(45px);
            }
            100% {
                opacity: 0.80;
                transform: scale(1) translate(0, 0);
                filter: blur(50px);
            }
        }

        @keyframes quickReloadBloom2 {
            0% {
                opacity: 0;
                transform: scale(0.6) translate(-40px, 40px);
                filter: blur(15px);
            }
            70% {
                opacity: 0.90;
                transform: scale(1.04) translate(0, 0);
                filter: blur(50px);
            }
            100% {
                opacity: 0.80;
                transform: scale(1) translate(0, 0);
                filter: blur(55px);
            }
        }

        @keyframes quickReloadBloomCenter {
            0% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.5);
                filter: blur(15px);
            }
            70% {
                opacity: 0.90;
                transform: translate(-50%, -50%) scale(1.05);
                filter: blur(42px);
            }
            100% {
                opacity: 0.80;
                transform: translate(-50%, -50%) scale(1);
                filter: blur(48px);
            }
        }

        @keyframes quickReloadBloom4 {
            0% {
                opacity: 0;
                transform: scale(0.5);
                filter: blur(15px);
            }
            100% {
                opacity: 0.75;
                transform: scale(1);
                filter: blur(45px);
            }
        }

        .sci-navbar {
            position: relative;
            z-index: 10;
            background-color: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-grey);
        }

        .auth-container {
            position: relative;
            z-index: 10;
            max-width: 480px;
            width: 100%;
            margin: auto;
            padding: 2.5rem 1rem;
            animation: authQuickIn 0.4s ease-out forwards;
        }

        @keyframes authQuickIn {
            0% {
                opacity: 0;
                transform: translateY(12px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-card {
            border: 1px solid #000000;
            background-color: #FFFFFF;
            padding: 2.25rem 2.25rem;
            box-shadow: 0 16px 45px rgba(0, 0, 0, 0.22);
            position: relative;
            z-index: 10;
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
            color: #000000;
            font-weight: 500;
            position: relative;
            z-index: 10;
        }
    </style>
</head>
<body>
    {{-- Stable Blurry Black Background Decor (80% Visibility) with Quick Reload Bloom Animation --}}
    <div class="auth-bg-decor" aria-hidden="true">
        <div class="auth-blur-shape auth-blur-1"></div>
        <div class="auth-blur-shape auth-blur-2"></div>
        <div class="auth-blur-shape auth-blur-3"></div>
        <div class="auth-blur-shape auth-blur-4"></div>
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
