@extends('layouts.app')

@section('title', 'My Activity Log')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom" style="border-color: var(--border-grey) !important;">
    <div>
        <div class="text-secondary-label">PERSONAL AUDIT TRAIL</div>
        <h1 class="h2 mb-0">My Laboratory Activity History</h1>
    </div>
</div>

<div class="sci-card">
    <div class="table-responsive">
        <table class="table-sci">
            <thead>
                <tr>
                    <th>LOG ID</th>
                    <th>TIMESTAMP (UTC)</th>
                    <th>ACTION PERFORMED</th>
                    <th>CASE ID</th>
                    <th>IP ADDRESS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="mono">#{{ $log->id }}</td>
                        <td class="mono" style="font-size: 12px;">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td>
                            <span class="mono fw-bold" style="font-size: 11px;">
                                {{ strtoupper(str_replace('_', ' ', $log->action)) }}
                            </span>
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
                        <td class="mono" style="font-size: 11px;">{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">No activity records logged.</td>
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

