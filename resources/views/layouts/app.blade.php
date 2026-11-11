<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — EMBRYOCASE</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/embryocase.css') }}">
    @yield('styles')
</head>
<body>
    <!-- Primary Scientific Navigation Bar -->
    <header class="sci-navbar">
        <a href="{{ auth()->user()?->isAdmin() ? route('admin.dashboard') : route('embryologist.dashboard') }}" class="sci-brand">
            <div class="sci-brand-logo">E</div>
            <div>
                <span class="sci-brand-name">EMBRYOCASE</span>
                <span class="sci-brand-tagline">Embryo Data &amp; Research Management System</span>
            </div>
        </a>

        <div class="d-flex align-items-center gap-3">
            <div class="d-none d-md-block text-end" style="line-height: 1.2;">
                <div class="fw-bold" style="font-size: 13px;">{{ auth()->user()?->name }}</div>
                <div class="text-secondary-label" style="font-size: 11px;">
                    {{ auth()->user()?->isAdmin() ? 'CENTRAL SYSTEM ADMINISTRATOR' : 'CLINICAL EMBRYOLOGIST' }}
                </div>
            </div>

            <div class="badge-sci {{ auth()->user()?->isAdmin() ? 'badge-sci-active' : 'badge-sci-neutral' }}">
                {{ strtoupper(auth()->user()?->role ?? 'GUEST') }}
            </div>

            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-sci-secondary" title="Terminate current laboratory session">
                    LOGOUT
                </button>
            </form>
        </div>
    </header>

    <!-- Sub-navigation Bar -->
    <nav class="sci-subnav">
        @if(auth()->user()?->isAdmin())
            <a href="{{ route('admin.dashboard') }}" class="sci-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                OVERVIEW
            </a>
            <a href="{{ route('admin.cases.index') }}" class="sci-nav-link {{ request()->routeIs('admin.cases*') ? 'active' : '' }}">
                ALL EMBRYO RECORDS
            </a>
            <a href="{{ route('admin.embryologists.index') }}" class="sci-nav-link {{ request()->routeIs('admin.embryologists*') ? 'active' : '' }}">
                EMBRYOLOGIST MANAGEMENT
            </a>
            <a href="{{ route('admin.activity.index') }}" class="sci-nav-link {{ request()->routeIs('admin.activity*') ? 'active' : '' }}">
                AUDIT HISTORY
            </a>
            <a href="{{ route('admin.backups.index') }}" class="sci-nav-link {{ request()->routeIs('admin.backups*') ? 'active' : '' }}">
                SYSTEM BACKUPS
            </a>
        @else
            <a href="{{ route('embryologist.dashboard') }}" class="sci-nav-link {{ request()->routeIs('embryologist.dashboard') ? 'active' : '' }}">
                MY WORKSPACE
            </a>
            <a href="{{ route('cases.index') }}" class="sci-nav-link {{ request()->routeIs('cases.index') || request()->routeIs('cases.show') ? 'active' : '' }}">
                EMBRYO CASES
            </a>
            <a href="{{ route('cases.create') }}" class="sci-nav-link {{ request()->routeIs('cases.create') ? 'active' : '' }}">
                + CREATE NEW CASE
            </a>
            <a href="{{ route('embryologist.activity') }}" class="sci-nav-link {{ request()->routeIs('embryologist.activity') ? 'active' : '' }}">
                MY ACCESS LOG
            </a>
        @endif
    </nav>

    <!-- Main Workspace Container -->
    <main class="container-fluid py-4 px-md-4">
        @if(session('status'))
            <div class="alert-sci alert-sci-success mb-3 alert-dismissible fade show" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close" style="filter: invert(1); font-size: 10px;"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert-sci alert-sci-error mb-3 alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size: 10px;"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Scientific System Footer -->
    <footer class="border-top py-3 px-4 mt-auto" style="border-color: var(--border-grey) !important; background-color: var(--bg-white);">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center text-muted" style="font-size: 12px;">
            <div>
                <strong>EMBRYOCASE</strong> &bull; Embryo Data &amp; Research Management System &bull; Research-Grade Monochromatic Architecture
            </div>
            <div class="mono mt-2 mt-md-0">
                SECURE REPOSITORY &bull; SHA-256 NON-DESTRUCTIVE STORAGE &bull; UTC {{ gmdate('Y-m-d H:i') }}
            </div>
        </div>
    </footer>

    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    @yield('scripts')
</body>
</html>
