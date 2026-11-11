@extends('layouts.app')

@section('title', 'Admin Overview')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 pb-2 border-bottom" style="border-color: var(--border-grey) !important;">
    <div>
        <div class="text-secondary-label">CENTRAL ADMINISTRATIVE REPOSITORY</div>
        <h1 class="h2 mb-0">System Overview &amp; Laboratory Controls</h1>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2">
        <a href="{{ route('admin.cases.index') }}" class="btn btn-sci-secondary">
            BROWSE ALL CASES
        </a>
        <a href="{{ route('admin.embryologists.create') }}" class="btn btn-sci-primary">
            + PROVISION EMBRYOLOGIST
        </a>
    </div>
</div>

<!-- Scientific Real Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="sci-stat-box">
            <div class="sci-stat-label">TOTAL EMBRYO CASES</div>
            <div class="sci-stat-value">{{ number_format($totalCases) }}</div>
            <div class="sci-stat-sub">Central database records</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="sci-stat-box">
            <div class="sci-stat-label">ACTIVE EMBRYOLOGISTS</div>
            <div class="sci-stat-value">{{ number_format($totalEmbryologists) }}</div>
            <div class="sci-stat-sub">Authorized laboratory staff</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="sci-stat-box">
            <div class="sci-stat-label">TOTAL IMAGES ARCHIVED</div>
            <div class="sci-stat-value">{{ number_format($totalImages) }}</div>
            <div class="sci-stat-sub">Private server repository</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="sci-stat-box">
            <div class="sci-stat-label">EDITED / ANNOTATED IMAGES</div>
            <div class="sci-stat-value">{{ number_format($totalEditedImages) }}</div>
            <div class="sci-stat-sub">Non-destructive vector edits</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recently Created Embryo Cases -->
    <div class="col-12 col-xl-7">
        <div class="sci-card">
            <div class="sci-card-header">
                <span class="sci-card-title">RECENTLY CREATED EMBRYO CASES</span>
                <a href="{{ route('admin.cases.index') }}" class="btn btn-sm btn-sci-secondary">VIEW ALL</a>
            </div>
            <div class="table-responsive">
                <table class="table-sci">
                    <thead>
                        <tr>
                            <th>CASE ID</th>
                            <th>EMBRYO #</th>
                            <th>COLLECTION DATE</th>
                            <th>EMBRYOLOGIST</th>
                            <th>IMAGES</th>
                            <th>STATUS</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentCases as $case)
                            <tr>
                                <td class="mono fw-bold">
                                    <a href="{{ route('cases.show', $case->case_id) }}" class="text-dark text-decoration-none">
                                        {{ $case->case_id }}
                                    </a>
                                </td>
                                <td>{{ $case->embryo_number }}</td>
                                <td>{{ $case->collection_date?->format('Y-m-d') }}</td>
                                <td>{{ $case->user?->name ?? 'N/A' }}</td>
                                <td class="mono">{{ $case->images->count() }}</td>
                                <td>
                                    @if($case->status === 'completed')
                                        <span class="badge-sci badge-sci-success">COMPLETED</span>
                                    @elseif($case->status === 'archived')
                                        <span class="badge-sci badge-sci-neutral">ARCHIVED</span>
                                    @else
                                        <span class="badge-sci badge-sci-pending">IN PROGRESS</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('cases.show', $case->case_id) }}" class="btn btn-sm btn-sci-secondary">
                                        OPEN
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No embryo cases registered yet in the database.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Audit History -->
    <div class="col-12 col-xl-5">
        <div class="sci-card">
            <div class="sci-card-header">
                <span class="sci-card-title">RECENT AUDIT TRAIL</span>
                <a href="{{ route('admin.activity.index') }}" class="btn btn-sm btn-sci-secondary">FULL LOG</a>
            </div>
            <div class="table-responsive">
                <table class="table-sci">
                    <thead>
                        <tr>
                            <th>ACTION</th>
                            <th>USER</th>
                            <th>CASE</th>
                            <th>TIMESTAMP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentActivities as $act)
                            <tr>
                                <td>
                                    <span class="mono" style="font-size: 11px; text-transform: uppercase;">
                                        {{ str_replace('_', ' ', $act->action) }}
                                    </span>
                                </td>
                                <td>{{ $act->user?->name ?? 'System' }}</td>
                                <td class="mono" style="font-size: 12px;">{{ $act->case_id ?? '—' }}</td>
                                <td class="text-muted" style="font-size: 11px;">
                                    {{ $act->created_at?->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No activity records logged.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
