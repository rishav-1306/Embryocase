@extends('layouts.app')

@section('title', 'Manage Embryologist: ' . $embryologist->name)

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 pb-2 border-bottom" style="border-color: var(--border-grey) !important;">
    <div>
        <div class="text-secondary-label">STAFF PROFILE &amp; ACCESS CONTROL</div>
        <h1 class="h2 mb-0">{{ $embryologist->name }}</h1>
        <div class="text-muted" style="font-size: 13px;">{{ $embryologist->email }}</div>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2">
        <a href="{{ route('admin.embryologists.index') }}" class="btn btn-sci-secondary">
            &larr; BACK TO LIST
        </a>
    </div>
</div>

<!-- Modal or Alert for One-Time Special Key Display -->
@if(session('new_credentials'))
    <div class="alert-sci mb-4" style="background-color: var(--bg-white); border: 2px solid var(--text-black);">
        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom" style="border-color: var(--border-grey) !important;">
            <strong class="text-uppercase" style="letter-spacing: 0.05em;">IMPORTANT: SECURE CREDENTIAL DISTRIBUTION NOTICE</strong>
            <span class="badge-sci badge-sci-active">DISPLAYED ONCE</span>
        </div>
        <p class="mb-2">The account has been provisioned. Deliver these four credentials directly to <strong>{{ session('new_credentials')['name'] }}</strong>:</p>
        <div class="p-3 mb-2" style="background-color: var(--bg-off-white); border: 1px solid var(--border-grey); font-family: var(--font-mono); font-size: 13px;">
            <div>1. Full Name:       <strong>{{ session('new_credentials')['name'] }}</strong></div>
            <div>2. Registered Email:<strong>{{ session('new_credentials')['email'] }}</strong></div>
            <div>3. Initial Password:<strong>{{ session('new_credentials')['password'] }}</strong></div>
            <div>4. Special Key:     <strong style="font-size: 16px; letter-spacing: 1px;">{{ session('new_credentials')['special_key'] }}</strong></div>
        </div>
        <small class="text-muted">The Special Key has been cryptographically hashed in MySQL. It cannot be recovered in plaintext again.</small>
    </div>
@endif

@if(session('regenerated_key'))
    <div class="alert-sci mb-4" style="background-color: var(--bg-white); border: 2px solid var(--text-black);">
        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom" style="border-color: var(--border-grey) !important;">
            <strong class="text-uppercase" style="letter-spacing: 0.05em;">NEW SPECIAL KEY GENERATED</strong>
            <span class="badge-sci badge-sci-active">ONE-TIME DISPLAY</span>
        </div>
        <p class="mb-1">Previous key was revoked. Distribute this new Special Key immediately:</p>
        <div class="p-3 mb-2" style="background-color: var(--bg-off-white); border: 1px solid var(--border-grey); font-family: var(--font-mono); font-size: 16px; font-weight: 700;">
            {{ session('regenerated_key') }}
        </div>
        <small class="text-muted">The key hash is stored securely in MySQL. The embryologist must use this key for all subsequent logins.</small>
    </div>
@endif

<div class="row g-4 mb-4">
    <!-- Left Column: Details and Control Actions -->
    <div class="col-12 col-lg-5">
        <!-- Account Status & Details -->
        <div class="sci-card mb-4">
            <div class="sci-card-header">
                <span class="sci-card-title">ACCOUNT STATUS &amp; METADATA</span>
                @if($embryologist->status === 'active')
                    <span class="badge-sci badge-sci-success">ACTIVE</span>
                @elseif($embryologist->status === 'suspended')
                    <span class="badge-sci badge-sci-danger">SUSPENDED</span>
                @else
                    <span class="badge-sci badge-sci-neutral">INACTIVE</span>
                @endif
            </div>
            <div class="sci-card-body">
                <table class="table-sci mb-3">
                    <tr>
                        <th style="width: 40%;">Account ID</th>
                        <td class="mono">#{{ $embryologist->id }}</td>
                    </tr>
                    <tr>
                        <th>Last Login</th>
                        <td class="mono">{{ $embryologist->last_login_at ? $embryologist->last_login_at->format('Y-m-d H:i:s') : 'Never' }}</td>
                    </tr>
                    <tr>
                        <th>Last Login IP</th>
                        <td class="mono">{{ $embryologist->last_login_ip ?? 'None recorded' }}</td>
                    </tr>
                    <tr>
                        <th>Registered Date</th>
                        <td class="mono">{{ $embryologist->created_at?->format('Y-m-d') }}</td>
                    </tr>
                    <tr>
                        <th>Cases Authored</th>
                        <td class="mono fw-bold">{{ $embryologist->embryoCases->count() }}</td>
                    </tr>
                </table>

                <!-- Status Update Form -->
                <form method="POST" action="{{ route('admin.embryologists.status', $embryologist->id) }}" class="mb-0">
                    @csrf
                    <label class="form-label">MODIFY ACCOUNT ACCESS STATE</label>
                    <div class="input-group">
                        <select name="status" class="form-select">
                            <option value="active" {{ $embryologist->status === 'active' ? 'selected' : '' }}>Active (Allow Login)</option>
                            <option value="inactive" {{ $embryologist->status === 'inactive' ? 'selected' : '' }}>Inactive (Temporary hold)</option>
                            <option value="suspended" {{ $embryologist->status === 'suspended' ? 'selected' : '' }}>Suspended (Access blocked)</option>
                        </select>
                        <button type="submit" class="btn btn-sci-secondary">UPDATE</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Special Key Management -->
        <div class="sci-card mb-4">
            <div class="sci-card-header">
                <span class="sci-card-title">SPECIAL KEY MANAGEMENT</span>
                @if($embryologist->special_key_hash)
                    <span class="badge-sci badge-sci-active">ACTIVE HASH VALID</span>
                @else
                    <span class="badge-sci badge-sci-danger">REVOKED</span>
                @endif
            </div>
            <div class="sci-card-body">
                <p class="text-meta mb-3">
                    The Special Key is mandatory for every login. It is hashed in MySQL and never saved as plaintext.
                </p>

                <div class="d-flex flex-column gap-2">
                    <form method="POST" action="{{ route('admin.embryologists.regenerate-key', $embryologist->id) }}" onsubmit="return confirm('Regenerate Special Key? The existing key will be permanently invalidated immediately.');">
                        @csrf
                        <button type="submit" class="btn btn-sci-primary w-100">
                            REGENERATE SPECIAL KEY
                        </button>
                    </form>

                    @if($embryologist->special_key_hash)
                        <form method="POST" action="{{ route('admin.embryologists.revoke-key', $embryologist->id) }}" onsubmit="return confirm('Revoke Special Key? This embryologist will NOT be able to log in until a new key is generated.');">
                            @csrf
                            <button type="submit" class="btn btn-sci-secondary w-100">
                                REVOKE SPECIAL KEY
                            </button>
                        </form>
                    @endif
                </div>

                <!-- Special Key Audit History -->
                <div class="mt-4 pt-3 border-top" style="border-color: var(--border-grey) !important;">
                    <div class="text-secondary-label mb-2">KEY ISSUANCE HISTORY</div>
                    <table class="table-sci" style="font-size: 11px;">
                        <thead>
                            <tr>
                                <th>ACTION</th>
                                <th>ISSUED BY</th>
                                <th>TIMESTAMP</th>
                                <th>STATE</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($embryologist->specialKeyHistory as $keyHist)
                                <tr>
                                    <td class="mono text-uppercase">{{ $keyHist->action }}</td>
                                    <td>{{ $keyHist->creator?->name ?? 'Admin' }}</td>
                                    <td class="mono">{{ $keyHist->created_at?->format('Y-m-d H:i') }}</td>
                                    <td>
                                        @if($keyHist->revoked_at)
                                            <span class="badge-sci badge-sci-neutral">REVOKED</span>
                                        @else
                                            <span class="badge-sci badge-sci-active">ACTIVE</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No key history recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Cases & Activity -->
    <div class="col-12 col-lg-7">
        <!-- Assigned Embryo Cases -->
        <div class="sci-card mb-4">
            <div class="sci-card-header">
                <span class="sci-card-title">EMBRYO CASES MANAGED ({{ $embryologist->embryoCases->count() }})</span>
            </div>
            <div class="table-responsive">
                <table class="table-sci">
                    <thead>
                        <tr>
                            <th>CASE ID</th>
                            <th>EMBRYO #</th>
                            <th>COLLECTION DATE</th>
                            <th>STATUS</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($embryologist->embryoCases as $c)
                            <tr>
                                <td class="mono fw-bold">
                                    <a href="{{ route('cases.show', $c->case_id) }}" class="text-dark text-decoration-none">
                                        {{ $c->case_id }}
                                    </a>
                                </td>
                                <td>{{ $c->embryo_number }}</td>
                                <td>{{ $c->collection_date?->format('Y-m-d') }}</td>
                                <td>
                                    <span class="badge-sci badge-sci-neutral">{{ strtoupper($c->status) }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('cases.show', $c->case_id) }}" class="btn btn-sm btn-sci-secondary">
                                        OPEN
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    No cases uploaded yet by this embryologist.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Activity History for this Embryologist -->
        <div class="sci-card">
            <div class="sci-card-header">
                <span class="sci-card-title">RECENT ACTIVITY LOG</span>
            </div>
            <div class="table-responsive">
                <table class="table-sci">
                    <thead>
                        <tr>
                            <th>ACTION</th>
                            <th>CASE ID</th>
                            <th>IP ADDRESS</th>
                            <th>TIMESTAMP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentLogs as $log)
                            <tr>
                                <td class="mono" style="font-size: 11px;">{{ strtoupper(str_replace('_', ' ', $log->action)) }}</td>
                                <td class="mono">{{ $log->case_id ?? '—' }}</td>
                                <td class="mono" style="font-size: 11px;">{{ $log->ip_address }}</td>
                                <td class="text-muted" style="font-size: 11px;">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No activity records recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
