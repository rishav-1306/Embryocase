@extends('layouts.app')

@section('title', 'Embryologist Workspace')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 pb-2 border-bottom" style="border-color: var(--border-grey) !important;">
    <div>
        <div class="text-secondary-label">CLINICAL WORKSPACE</div>
        <h1 class="h2 mb-0">Embryo Data Management</h1>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2">
        <a href="{{ route('cases.create') }}" class="btn btn-sci-primary px-3 py-2">
            + CREATE NEW EMBRYO CASE
        </a>
    </div>
</div>

<!-- Embryologist Real Statistics -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="sci-stat-box">
            <div class="sci-stat-label">TOTAL CASES AUTHORED</div>
            <div class="sci-stat-value">{{ number_format($totalCases) }}</div>
            <div class="sci-stat-sub">Under your management</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="sci-stat-box">
            <div class="sci-stat-label">CASES IN PROGRESS</div>
            <div class="sci-stat-value">{{ number_format($casesInProgress) }}</div>
            <div class="sci-stat-sub">Active incubation/monitoring</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="sci-stat-box">
            <div class="sci-stat-label">COMPLETED CASES</div>
            <div class="sci-stat-value">{{ number_format($casesCompleted) }}</div>
            <div class="sci-stat-sub">Day 5/6 finalized</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="sci-stat-box">
            <div class="sci-stat-label">IMAGES UPLOADED</div>
            <div class="sci-stat-value">{{ number_format($totalImagesUploaded) }}</div>
            <div class="sci-stat-sub">Microscopy acquisitions</div>
        </div>
    </div>
</div>

<!-- Search & Quick Navigation -->
<div class="sci-card mb-4">
    <div class="sci-card-body p-3">
        <form method="GET" action="{{ route('cases.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text"
                       name="search"
                       class="form-control"
                       placeholder="Search Embryo Number, Case ID, or Parent...">
            </div>
            <div class="col-md-3">
                <input type="date"
                       name="collection_date"
                       class="form-control"
                       placeholder="Collection Date">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sci-secondary w-100">SEARCH CASES</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Cases -->
    <div class="col-12 col-xl-8">
        <div class="sci-card">
            <div class="sci-card-header">
                <span class="sci-card-title">RECENT EMBRYO CASES</span>
                <a href="{{ route('cases.index') }}" class="btn btn-sm btn-sci-secondary">VIEW ALL</a>
            </div>
            <div class="table-responsive">
                <table class="table-sci">
                    <thead>
                        <tr>
                            <th>CASE ID</th>
                            <th>EMBRYO NUMBER</th>
                            <th>COLLECTION DATE</th>
                            <th>DAYS</th>
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
                                <td class="mono">Day 1–{{ $case->total_days }}</td>
                                <td class="mono">{{ $case->images->count() }}</td>
                                <td>
                                    @if($case->status === 'completed')
                                        <span class="badge-sci badge-sci-success">COMPLETED</span>
                                    @else
                                        <span class="badge-sci badge-sci-pending">IN PROGRESS</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('cases.show', $case->case_id) }}" class="btn btn-sm btn-sci-secondary">
                                        OPEN CASE
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    No embryo cases uploaded yet. Click <strong>+ CREATE NEW EMBRYO CASE</strong> to register your first clinical case.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Personal Activities -->
    <div class="col-12 col-xl-4">
        <div class="sci-card">
            <div class="sci-card-header">
                <span class="sci-card-title">MY RECENT ACTIONS</span>
            </div>
            <div class="table-responsive">
                <table class="table-sci">
                    <thead>
                        <tr>
                            <th>ACTION</th>
                            <th>CASE</th>
                            <th>TIME</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentActivities as $act)
                            <tr>
                                <td>
                                    <span class="mono" style="font-size: 11px;">
                                        {{ strtoupper(str_replace('_', ' ', $act->action)) }}
                                    </span>
                                </td>
                                <td class="mono" style="font-size: 11px;">{{ $act->case_id ?? '—' }}</td>
                                <td class="text-muted" style="font-size: 11px;">{{ $act->created_at?->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">No personal logs recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

