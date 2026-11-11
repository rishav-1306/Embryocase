@extends('layouts.app')

@section('title', 'Embryologist Management')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 pb-2 border-bottom" style="border-color: var(--border-grey) !important;">
    <div>
        <div class="text-secondary-label">STAFF ACCESS &amp; CREDENTIALS</div>
        <h1 class="h2 mb-0">Embryologist Management</h1>
    </div>
    <div class="mt-3 mt-md-0">
        <a href="{{ route('admin.embryologists.create') }}" class="btn btn-sci-primary">
            + CREATE EMBRYOLOGIST ACCOUNT
        </a>
    </div>
</div>

<!-- Search & Filter Controls -->
<div class="sci-card mb-4">
    <div class="sci-card-body p-3">
        <form method="GET" action="{{ route('admin.embryologists.index') }}" class="row g-2 align-items-center">
            <div class="col-md-6 col-lg-5">
                <input type="text"
                       name="search"
                       class="form-control"
                       placeholder="Search by full name or email..."
                       value="{{ request('search') }}">
            </div>
            <div class="col-md-4 col-lg-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>
            <div class="col-md-2 col-lg-2">
                <button type="submit" class="btn btn-sci-secondary w-100">FILTER</button>
            </div>
            @if(request()->hasAny(['search', 'status']))
                <div class="col-md-2 col-lg-2">
                    <a href="{{ route('admin.embryologists.index') }}" class="btn btn-sci-subtle w-100">RESET</a>
                </div>
            @endif
        </form>
    </div>
</div>

<!-- Embryologists Table -->
<div class="sci-card">
    <div class="table-responsive">
        <table class="table-sci">
            <thead>
                <tr>
                    <th>FULL NAME</th>
                    <th>EMAIL ADDRESS</th>
                    <th>SPECIAL KEY STATUS</th>
                    <th>ACCOUNT STATUS</th>
                    <th>CASES UPLOADED</th>
                    <th>LAST LOGIN</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($embryologists as $emb)
                    <tr>
                        <td class="fw-bold">
                            <a href="{{ route('admin.embryologists.show', $emb->id) }}" class="text-dark text-decoration-none">
                                {{ $emb->name }}
                            </a>
                        </td>
                        <td>{{ $emb->email }}</td>
                        <td>
                            @if($emb->special_key_hash)
                                <span class="badge-sci badge-sci-active">VALID KEY ISSUED</span>
                            @else
                                <span class="badge-sci badge-sci-danger">KEY REVOKED / MISSING</span>
                            @endif
                        </td>
                        <td>
                            @if($emb->status === 'active')
                                <span class="badge-sci badge-sci-success">ACTIVE</span>
                            @elseif($emb->status === 'suspended')
                                <span class="badge-sci badge-sci-danger">SUSPENDED</span>
                            @else
                                <span class="badge-sci badge-sci-neutral">INACTIVE</span>
                            @endif
                        </td>
                        <td class="mono">{{ $emb->embryo_cases_count }}</td>
                        <td class="text-muted" style="font-size: 12px;">
                            {{ $emb->last_login_at ? $emb->last_login_at->format('Y-m-d H:i') : 'Never' }}
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('admin.embryologists.show', $emb->id) }}" class="btn btn-sm btn-sci-secondary">
                                    MANAGE
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            No embryologists matching query.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($embryologists->hasPages())
        <div class="p-3 border-top" style="border-color: var(--border-grey) !important;">
            {{ $embryologists->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection

