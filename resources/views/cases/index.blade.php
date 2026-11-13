@extends('layouts.app')

@section('title', 'Embryo Records')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 pb-2 border-bottom" style="border-color: var(--border-grey) !important;">
    <div>
        <div class="text-secondary-label">CLINICAL DATABASE</div>
        <h1 class="h2 mb-0">Embryo Case Records</h1>
    </div>
    <div class="mt-3 mt-md-0 d-flex gap-2">
        <a href="{{ route('cases.create') }}" class="btn btn-sci-primary">
            + CREATE NEW EMBRYO CASE
        </a>
    </div>
</div>

<!-- Filters -->
<div class="sci-card mb-4">
    <div class="sci-card-body p-3">
        <form method="GET" action="{{ route('cases.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <input type="text"
                       name="search"
                       class="form-control"
                       placeholder="Search Case ID, Embryo Number, or Parental names..."
                       value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <input type="date"
                       name="collection_date"
                       class="form-control"
                       value="{{ request('collection_date') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sci-secondary w-100">FILTER</button>
            </div>
            @if(request()->hasAny(['search', 'collection_date', 'status']))
                <div class="col-12 mt-2">
                    <a href="{{ route('cases.index') }}" class="text-secondary text-decoration-none" style="font-size: 12px;">
                        &times; Reset filters
                    </a>
                </div>
            @endif
        </form>
    </div>
</div>

<!-- Cases Table -->
<div class="sci-card">
    <div class="table-responsive">
        <table class="table-sci">
            <thead>
                <tr>
                    <th>CASE ID</th>
                    <th>EMBRYO NUMBER</th>
                    <th>FATHER</th>
                    <th>MOTHER</th>
                    <th>COLLECTION DATE</th>
                    <th>TOTAL DAYS</th>
                    <th>IMAGES</th>
                    <th>STATUS</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cases as $case)
                    <tr>
                        <td class="mono fw-bold">
                            <a href="{{ route('cases.show', $case->case_id) }}" class="text-dark text-decoration-none">
                                {{ $case->case_id }}
                            </a>
                        </td>
                        <td>{{ $case->embryo_number }}</td>
                        <td>{{ $case->father_name }}</td>
                        <td>{{ $case->mother_name }}</td>
                        <td class="mono">{{ $case->collection_date?->format('Y-m-d') }}</td>
                        <td class="mono">Day 1–{{ $case->total_days }}</td>
                        <td class="mono">
                            {{ $case->images->count() }}
                            @php $editedCount = $case->images->where('has_edit', true)->count(); @endphp
                            @if($editedCount > 0)
                                <span class="badge-sci badge-sci-active ms-1">{{ $editedCount }}E</span>
                            @endif
                        </td>
                        <td>
                            @if($case->status === 'completed')
                                <span class="badge-sci badge-sci-success">COMPLETED</span>
                            @else
                                <span class="badge-sci badge-sci-pending">IN PROGRESS</span>
                            @endif
                            @if($case->isTodayImageMissing())
                                <span class="badge bg-danger text-white mono d-block mt-1" style="font-size: 9px;" title="Today's acquisition photograph has not been uploaded yet">
                                    DAY {{ $case->today_development_day }} PENDING
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('cases.show', $case->case_id) }}" class="btn btn-sm btn-sci-secondary">
                                    OPEN
                                </a>
                                <a href="{{ route('cases.export', $case->case_id) }}" class="btn btn-sm btn-sci-subtle" title="Export ZIP Archive">
                                    ZIP
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            No embryo cases registered. Click <strong>+ CREATE NEW EMBRYO CASE</strong> to begin.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($cases->hasPages())
        <div class="p-3 border-top" style="border-color: var(--border-grey) !important;">
            {{ $cases->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection

