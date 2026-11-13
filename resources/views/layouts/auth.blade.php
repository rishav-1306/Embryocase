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
            background-color: #F5F5F7;
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
            pointer-events: none;
            will-change: transform, opacity, filter;
        }

        /* Shape 1: Top-Right Deep Black Cloud */
        .auth-blur-1 {
            width: 580px;
            height: 580px;
            top: -160px;
            right: -140px;
            background: radial-gradient(circle, rgba(0, 0, 0, 0.38) 0%, rgba(20, 20, 20, 0.20) 50%, rgba(0, 0, 0, 0) 75%);
            filter: blur(65px);
            animation: quickReloadBloom1 0.65s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Shape 2: Bottom-Left Deep Black Cloud */
        .auth-blur-2 {
            width: 620px;
            height: 620px;
            bottom: -180px;
            left: -160px;
            background: radial-gradient(circle, rgba(0, 0, 0, 0.35) 0%, rgba(20, 20, 20, 0.18) 50%, rgba(0, 0, 0, 0) 75%);
            filter: blur(70px);
            animation: quickReloadBloom2 0.65s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Shape 3: Central Aura Behind Login Card */
        .auth-blur-3 {
            width: 520px;
            height: 520px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: radial-gradient(circle, rgba(0, 0, 0, 0.28) 0%, rgba(30, 30, 30, 0.14) 50%, rgba(0, 0, 0, 0) 75%);
            filter: blur(60px);
            animation: quickReloadBloomCenter 0.65s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Shape 4: Mid-Right Subtle Secondary Orb */
        .auth-blur-4 {
            width: 380px;
            height: 380px;
            top: 60%;
            right: -80px;
            background: radial-gradient(circle, rgba(0, 0, 0, 0.25) 0%, rgba(40, 40, 40, 0.12) 50%, rgba(0, 0, 0, 0) 75%);
            filter: blur(55px);
            animation: quickReloadBloom4 0.65s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Quick reload animation: visible rapid bloom on load/reload, then stays completely stable and still */
        @keyframes quickReloadBloom1 {
            0% {
                opacity: 0;
                transform: scale(0.5) translate(30px, -30px);
                filter: blur(20px);
            }
            70% {
                opacity: 1;
                transform: scale(1.05) translate(0, 0);
                filter: blur(55px);
            }
            100% {
                opacity: 1;
                transform: scale(1) translate(0, 0);
                filter: blur(65px);
            }
        }

        @keyframes quickReloadBloom2 {
            0% {
                opacity: 0;
                transform: scale(0.5) translate(-30px, 30px);
                filter: blur(20px);
            }
            70% {
                opacity: 1;
                transform: scale(1.05) translate(0, 0);
                filter: blur(60px);
            }
            100% {
                opacity: 1;
                transform: scale(1) translate(0, 0);
                filter: blur(70px);
            }
        }

        @keyframes quickReloadBloomCenter {
            0% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.4);
                filter: blur(20px);
            }
            70% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1.06);
                filter: blur(50px);
            }
            100% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
                filter: blur(60px);
            }
        }

        @keyframes quickReloadBloom4 {
            0% {
                opacity: 0;
                transform: scale(0.5);
                filter: blur(20px);
            }
            100% {
                opacity: 1;
                transform: scale(1);
                filter: blur(55px);
            }
        }

        .sci-navbar {
            position: relative;
            z-index: 2;
            background-color: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-grey);
        }

        .auth-container {
            position: relative;
            z-index: 1;
            max-width: 480px;
            width: 100%;
            margin: auto;
            padding: 2.5rem 1rem;
            animation: authQuickIn 0.4s ease-out forwards;
        }

        @keyframes authQuickIn {
            0% {
                opacity: 0;
                transform: translateY(10px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-card {
            border: 1px solid var(--border-grey);
            background-color: #FFFFFF;
            padding: 2.25rem 2.25rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
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
            color: var(--text-dark-grey);
            position: relative;
            z-index: 2;
        }
    </style>
</head>
<body>
    {{-- Stable Blurry Black Background Decor with Quick Reload Bloom Animation --}}
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
