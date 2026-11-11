@extends('layouts.app')

@section('title', 'Audit Trail')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 pb-2 border-bottom" style="border-color: var(--border-grey) !important;">
    <div>
        <div class="text-secondary-label">SECURITY &amp; COMPLIANCE AUDIT</div>
        <h1 class="h2 mb-0">System Activity History</h1>
    </div>
</div>

<!-- Filters -->
<div class="sci-card mb-4">
    <div class="sci-card-body p-3">
        <form method="GET" action="{{ route('admin.activity.index') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <input type="text"
                       name="case_id"
                       class="form-control"
                       placeholder="Filter by Case ID (e.g. EMB-2026-0001)..."
                       value="{{ request('case_id') }}">
            </div>
            <div class="col-md-3">
                <select name="user_id" class="form-select">
                    <option value="">All Users / Staff</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                            {{ $u->name }} ({{ $u->role }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="date"
                       name="date"
                       class="form-control"
                       value="{{ request('date') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sci-secondary w-100">FILTER LOGS</button>
            </div>
            @if(request()->hasAny(['case_id', 'user_id', 'date']))
                <div class="col-md-1">
                    <a href="{{ route('admin.activity.index') }}" class="btn btn-sci-subtle w-100">RESET</a>
                </div>
            @endif
        </form>
    </div>
</div>

<!-- Activity Logs Table -->
<div class="sci-card">
    <div class="table-responsive">
        <table class="table-sci">
            <thead>
                <tr>
                    <th>LOG ID</th>
                    <th>TIMESTAMP (UTC)</th>
                    <th>ACTION PERFORMED</th>
                    <th>USER / ACTOR</th>
                    <th>RELATED CASE</th>
                    <th>IP ADDRESS</th>
                    <th>METADATA DETAILS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="mono">#{{ $log->id }}</td>
                        <td class="mono" style="font-size: 12px;">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td>
                            <span class="mono fw-bold" style="font-size: 11px; text-transform: uppercase;">
                                {{ str_replace('_', ' ', $log->action) }}
                            </span>
                        </td>
                        <td>
                            @if($log->user)
                                <strong>{{ $log->user->name }}</strong>
                                <span class="badge-sci badge-sci-neutral ms-1" style="font-size: 9px;">{{ strtoupper($log->user->role) }}</span>
                            @else
                                <span class="text-muted">Unauthenticated / System</span>
                            @endif
                        </td>
                        <td class="mono">
                            @if($log->case_id)
                                <a href="{{ route('cases.show', $log->case_id) }}" class="text-dark">
                                    {{ $log->case_id }}
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="mono" style="font-size: 11px;">{{ $log->ip_address ?? '—' }}</td>
                        <td style="font-size: 11px; max-width: 320px;">
                            @if(!empty($log->metadata))
                                <code class="mono" style="background-color: var(--bg-off-white); padding: 2px 4px; display: block; overflow-x: auto;">
                                    {{ json_encode($log->metadata) }}
                                </code>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">No audit logs matching criteria.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="p-3 border-top" style="border-color: var(--border-grey) !important;">
            {{ $logs->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
