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

@if(isset($pendingTodayCases) && $pendingTodayCases->count() > 0)
    <div class="alert-sci mb-4 p-3" style="background-color: #FFFBE6; border: 1px solid #000; border-left: 6px solid #D9381E;">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <div class="d-flex align-items-center gap-2">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D9381E" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h20.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <strong style="color: #000; font-size: 14px; letter-spacing: 0.5px;">CENTRAL ALERT: TODAY'S EMBRYO ACQUISITION IMAGES MISSING</strong>
            </div>
            <span class="badge-sci badge-sci-active mono" style="font-size: 11px;">{{ $pendingTodayCases->count() }} ACTIVE PENDING CASE(S)</span>
        </div>
        <p class="text-dark mb-2" style="font-size: 13px;">
            The following active laboratory embryo cases are scheduled for observation today, but <strong>no microscopy photograph has been uploaded yet for today</strong>:
        </p>
        <div class="table-responsive">
            <table class="table-sci mb-0" style="background-color: #fff;">
                <thead>
                    <tr>
                        <th>Embryo Number</th>
                        <th>Internal Case ID</th>
                        <th>Assigned Embryologist</th>
                        <th>Today's Scheduled Stage</th>
                        <th>Collection Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pendingTodayCases as $pCase)
                        <tr>
                            <td class="mono fw-bold">{{ $pCase->embryo_number }}</td>
                            <td class="mono">{{ $pCase->case_id }}</td>
                            <td>{{ $pCase->user?->name ?? 'Unassigned' }}</td>
                            <td>
                                <span class="badge bg-danger text-white mono" style="font-size: 11px;">DAY {{ $pCase->today_development_day }}</span>
                                <span class="text-muted ms-1" style="font-size: 11px;">(Today)</span>
                            </td>
                            <td class="mono">{{ $pCase->collection_date?->format('Y-m-d') }}</td>
                            <td>
                                <a href="{{ route('cases.show', ['caseId' => $pCase->case_id, 'day' => $pCase->today_development_day]) }}" class="btn btn-sm btn-sci-primary py-0 px-2" style="font-size: 11px;">
                                    OPEN CASE &rarr;
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

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

