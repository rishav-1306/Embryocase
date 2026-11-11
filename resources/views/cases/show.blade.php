@extends('layouts.app')

@section('title', 'Embryo Case: ' . $case->case_id)

@section('content')
<!-- Case Top Navigation & Summary -->
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center mb-4 pb-3 border-bottom" style="border-color: var(--border-grey) !important;">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="text-secondary-label">CASE DOSSIER</span>
            <span class="mono fw-bold" style="font-size: 15px;">{{ $case->case_id }}</span>
            @if($case->status === 'completed')
                <span class="badge-sci badge-sci-success">COMPLETED</span>
            @elseif($case->status === 'archived')
                <span class="badge-sci badge-sci-neutral">ARCHIVED</span>
            @else
                <span class="badge-sci badge-sci-pending">IN PROGRESS</span>
            @endif
        </div>
        <h1 class="h2 mb-1">Embryo #{{ $case->embryo_number }}</h1>
        <div class="text-muted" style="font-size: 13px;">
            Collected: <strong>{{ $case->collection_date?->format('Y-m-d') }}</strong> at <strong>{{ $case->collection_time }}</strong> &bull;
            Parents: <strong>{{ $case->father_name }}</strong> &amp; <strong>{{ $case->mother_name }}</strong> &bull;
            Embryologist: <strong>{{ $case->user?->name ?? 'N/A' }}</strong>
        </div>
    </div>

    <div class="mt-3 mt-lg-0 d-flex flex-wrap gap-2">
        <!-- View Toggle -->
        <div class="btn-group" role="group">
            <a href="{{ route('cases.show', ['caseId' => $case->case_id, 'view' => 'folder', 'day' => $activeDay]) }}"
               class="btn btn-sm {{ $viewMode === 'folder' ? 'btn-sci-primary' : 'btn-sci-secondary' }}">
                FOLDER VIEW
            </a>
            <a href="{{ route('cases.show', ['caseId' => $case->case_id, 'view' => 'timeline']) }}"
               class="btn btn-sm {{ $viewMode === 'timeline' ? 'btn-sci-primary' : 'btn-sci-secondary' }}">
                TIMELINE VIEW
            </a>
        </div>

        <a href="{{ route('cases.compare', $case->case_id) }}" class="btn btn-sm btn-sci-secondary">
            COMPARE IMAGES
        </a>

        <a href="{{ route('cases.export', $case->case_id) }}" class="btn btn-sm btn-sci-secondary" title="Export complete case ZIP">
            EXPORT CASE (ZIP)
        </a>

        <a href="{{ route('cases.edit', $case->case_id) }}" class="btn btn-sm btn-sci-secondary">
            EDIT METADATA
        </a>

        <button type="button" class="btn btn-sm btn-sci-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
            + UPLOAD IMAGES
        </button>
    </div>
</div>

@if($viewMode === 'timeline')
    <!-- ==================== TIMELINE VIEW ==================== -->
    <div class="sci-card">
        <div class="sci-card-header">
            <span class="sci-card-title">DEVELOPMENT CHRONOLOGICAL TIMELINE</span>
            <span class="mono" style="font-size: 12px;">TOTAL ACQUISITIONS: {{ $case->images->count() }}</span>
        </div>
        <div class="sci-card-body p-4">
            @php
                $imagesByDay = $case->images->groupBy('day_number');
            @endphp

            @for($d = 1; $d <= $case->total_days; $d++)
                @php $dayImgs = $imagesByDay->get($d, collect()); @endphp
                <div class="mb-5 pb-3 border-bottom" style="border-color: var(--border-grey) !important;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge-sci badge-sci-active" style="font-size: 13px; padding: 4px 10px;">DAY {{ $d }}</span>
                            <span class="text-muted" style="font-size: 13px;">
                                @if($case->collection_date)
                                    Estimated Date: {{ $case->collection_date->copy()->addDays($d - 1)->format('Y-m-d') }}
                                @endif
                            </span>
                        </div>
                        <span class="mono text-muted" style="font-size: 12px;">{{ $dayImgs->count() }} Images Captured</span>
                    </div>

                    @if($dayImgs->count() > 0)
                        <div class="embryo-gallery">
                            @foreach($dayImgs->sortBy('capture_time') as $img)
                                <div class="embryo-card">
                                    <div class="embryo-thumb-container">
                                        <img src="{{ route('images.serve', ['id' => $img->id, 'type' => $img->has_edit ? 'edited' : 'original']) }}"
                                             alt="Embryo acquisition {{ $img->filename }}"
                                             class="embryo-thumb"
                                             loading="lazy">

                                        @if($img->has_edit)
                                            <span class="position-absolute top-0 end-0 m-2 badge-sci badge-sci-active" style="font-size: 10px;">
                                                EDITED (_E)
                                            </span>
                                        @endif
                                    </div>
                                    <div class="embryo-card-info">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="embryo-time mono">{{ $img->capture_time }}</span>
                                            <span class="text-muted mono" style="font-size: 11px;">{{ $img->formatted_file_size }}</span>
                                        </div>
                                        <div class="text-muted mono mb-2" style="font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            {{ $img->filename }}
                                        </div>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('images.view', $img->id) }}" class="btn btn-sm btn-sci-secondary w-50" style="font-size: 11px;">
                                                VIEW
                                            </a>
                                            <a href="{{ route('images.editor', $img->id) }}" class="btn btn-sm btn-sci-primary w-50" style="font-size: 11px;">
                                                ANNOTATE
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-3 text-muted" style="background-color: var(--bg-off-white); border: 1px dashed var(--border-grey); font-size: 13px;">
                            No microscopy images recorded for Day {{ $d }}.
                        </div>
                    @endif
                </div>
            @endfor
        </div>
    </div>

@else
    <!-- ==================== FOLDER VIEW (DEFAULT) ==================== -->
    <div class="row g-4">
        <!-- Left: Case Metadata & Tree Directory -->
        <div class="col-12 col-xl-4">
            <!-- Metadata Summary Card -->
            <div class="sci-card mb-4">
                <div class="sci-card-header">
                    <span class="sci-card-title">CASE METADATA</span>
                    <a href="{{ route('cases.edit', $case->case_id) }}" class="btn btn-sm btn-sci-secondary">EDIT</a>
                </div>
                <div class="sci-card-body p-0">
                    <table class="table-sci mb-0">
                        <tr>
                            <th style="width: 42%;">Internal Case ID</th>
                            <td class="mono fw-bold">{{ $case->case_id }}</td>
                        </tr>
                        <tr>
                            <th>Embryo Number</th>
                            <td class="mono">{{ $case->embryo_number }}</td>
                        </tr>
                        <tr>
                            <th>Father's Name</th>
                            <td>{{ $case->father_name }}</td>
                        </tr>
                        <tr>
                            <th>Mother's Name</th>
                            <td>{{ $case->mother_name }}</td>
                        </tr>
                        <tr>
                            <th>Collection Date</th>
                            <td class="mono">{{ $case->collection_date?->format('Y-m-d') }}</td>
                        </tr>
                        <tr>
                            <th>Collection Time</th>
                            <td class="mono">{{ $case->collection_time }}</td>
                        </tr>
                        <tr>
                            <th>Embryologist</th>
                            <td>{{ $case->user?->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Total Days Tracked</th>
                            <td class="mono">Day 1 to Day {{ $case->total_days }}</td>
                        </tr>
                    </table>

                    @if($case->lab_notes)
                        <div class="p-3 border-top" style="border-color: var(--border-grey) !important;">
                            <div class="text-secondary-label mb-1">LABORATORY NOTES:</div>
                            <div class="text-meta" style="font-size: 12px; white-space: pre-line;">{{ $case->lab_notes }}</div>
                        </div>
                    @endif

                    @if($case->additional_observations)
                        <div class="p-3 border-top" style="border-color: var(--border-grey) !important;">
                            <div class="text-secondary-label mb-1">ADDITIONAL OBSERVATIONS:</div>
                            <div class="text-meta" style="font-size: 12px; white-space: pre-line;">{{ $case->additional_observations }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Clinical Observations List -->
            <div class="sci-card mb-4">
                <div class="sci-card-header">
                    <span class="sci-card-title">CLINICAL OBSERVATIONS ({{ $case->observations->count() }})</span>
                    <button type="button" class="btn btn-sm btn-sci-secondary" data-bs-toggle="collapse" data-bs-target="#newObsCollapse">
                        + ADD
                    </button>
                </div>
                <div class="sci-card-body p-3">
                    <!-- Collapse Form -->
                    <div class="collapse mb-3 pb-3 border-bottom" id="newObsCollapse" style="border-color: var(--border-grey) !important;">
                        <form method="POST" action="{{ route('cases.observations.store', $case->case_id) }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label" style="font-size: 11px;">Observation Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control form-control-sm" placeholder="e.g. Cleavage rate observation" required>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label" style="font-size: 11px;">Day</label>
                                    <select name="day_number" class="form-select form-select-sm">
                                        @for($d = 1; $d <= $case->total_days; $d++)
                                            <option value="{{ $d }}" {{ $activeDay == $d ? 'selected' : '' }}>Day {{ $d }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label" style="font-size: 11px;">Related Image (Opt)</label>
                                    <select name="image_id" class="form-select form-select-sm">
                                        <option value="">None</option>
                                        @foreach($case->images as $cImg)
                                            <option value="{{ $cImg->id }}">Day {{ $cImg->day_number }} - {{ $cImg->filename }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label" style="font-size: 11px;">Clinical Description <span class="text-danger">*</span></label>
                                <textarea name="description" rows="2" class="form-control form-control-sm" placeholder="Morphological parameters, blastocoel cavity note..." required></textarea>
                            </div>
                            <div class="d-flex justify-content-end gap-1">
                                <button type="button" class="btn btn-sm btn-sci-subtle" data-bs-toggle="collapse" data-bs-target="#newObsCollapse">Cancel</button>
                                <button type="submit" class="btn btn-sm btn-sci-primary">Record Note</button>
                            </div>
                        </form>
                    </div>

                    <!-- Existing Observations -->
                    @forelse($case->observations as $obs)
                        <div class="p-2 mb-2" style="background-color: var(--bg-off-white); border: 1px solid var(--border-grey);">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong style="font-size: 13px;">{{ $obs->title }}</strong>
                                <span class="badge-sci badge-sci-neutral" style="font-size: 9px;">DAY {{ $obs->day_number ?? '—' }}</span>
                            </div>
                            <div class="text-meta mb-1" style="font-size: 12px;">{{ $obs->description }}</div>
                            <div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 10px;">
                                <span>{{ $obs->user?->name }} &bull; {{ $obs->created_at?->format('Y-m-d H:i') }}</span>
                                <form method="POST" action="{{ route('observations.destroy', $obs->id) }}" onsubmit="return confirm('Remove observation note?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-link p-0 text-muted" style="font-size: 10px;">Delete</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted text-center py-3" style="font-size: 12px;">No clinical observations recorded yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right: Day Gallery & Management -->
        <div class="col-12 col-xl-8">
            <div class="sci-card">
                <!-- Day Navigation Tabs -->
                <div class="day-tab-nav">
                    @for($d = 1; $d <= $case->total_days; $d++)
                        @php $count = $case->images->where('day_number', $d)->count(); @endphp
                        <a href="{{ route('cases.show', ['caseId' => $case->case_id, 'view' => 'folder', 'day' => $d]) }}"
                           class="day-tab-item {{ $activeDay == $d ? 'active' : '' }}">
                            <span>DAY {{ $d }}</span>
                            <span class="day-tab-count">{{ $count }}</span>
                        </a>
                    @endfor

                    <form method="POST" action="{{ route('cases.add-day', $case->case_id) }}" class="m-0 align-self-center ps-2">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-sci-subtle py-1 px-2" title="Extend case with Day {{ $case->total_days + 1 }}">
                            + ADD DAY
                        </button>
                    </form>
                </div>

                <!-- Active Day Action Bar -->
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center" style="background-color: var(--bg-white); border-color: var(--border-grey) !important;">
                    <div>
                        <h3 class="mb-0">DAY {{ $activeDay }} REPOSITORY</h3>
                        <div class="text-muted" style="font-size: 12px;">
                            {{ $dayImages->count() }} acquisition file(s) &bull;
                            Path: <code class="mono" style="font-size: 11px;">storage/app/private/embryos/{{ $case->case_id }}/Day_{{ $activeDay }}/</code>
                        </div>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-sci-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            + UPLOAD TO DAY {{ $activeDay }}
                        </button>
                    </div>
                </div>

                <!-- Image Gallery Grid -->
                <div class="sci-card-body p-3">
                    @if($dayImages->count() > 0)
                        <div class="embryo-gallery">
                            @foreach($dayImages->sortBy('capture_time') as $img)
                                <div class="embryo-card">
                                    <div class="embryo-thumb-container">
                                        <img src="{{ route('images.serve', ['id' => $img->id, 'type' => $img->has_edit ? 'edited' : 'original']) }}"
                                             alt="{{ $img->filename }}"
                                             class="embryo-thumb"
                                             loading="lazy">

                                        @if($img->has_edit)
                                            <span class="position-absolute top-0 end-0 m-2 badge-sci badge-sci-active" style="font-size: 10px;">
                                                EDITED (_E)
                                            </span>
                                        @endif
                                    </div>
                                    <div class="embryo-card-info">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="embryo-time mono">{{ $img->capture_time }}</span>
                                            <span class="text-muted mono" style="font-size: 11px;">{{ $img->formatted_file_size }}</span>
                                        </div>
                                        <div class="text-muted mono mb-2" style="font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $img->filename }}">
                                            {{ $img->filename }}
                                        </div>
                                        @if($img->width && $img->height)
                                            <div class="text-muted mono mb-2" style="font-size: 10px;">
                                                {{ $img->width }} &times; {{ $img->height }} px
                                            </div>
                                        @endif
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('images.view', $img->id) }}" class="btn btn-sm btn-sci-secondary w-50" style="font-size: 11px;">
                                                VIEW
                                            </a>
                                            <a href="{{ route('images.editor', $img->id) }}" class="btn btn-sm btn-sci-primary w-50" style="font-size: 11px;">
                                                ANNOTATE
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="text-secondary-label mb-2">NO IMAGES RECORDED FOR DAY {{ $activeDay }}</div>
                            <p class="text-muted mb-3" style="font-size: 13px;">
                                Upload microscopy photographs for Day {{ $activeDay }} using the button below.
                            </p>
                            <button type="button" class="btn btn-sci-primary px-3" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                + UPLOAD DAY {{ $activeDay }} IMAGES
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif

<!-- ==================== UPLOAD MODAL ==================== -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel" style="font-size: 15px; font-weight: 700; text-transform: uppercase;">
                    Upload Embryo Microscopy Images &bull; {{ $case->case_id }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('cases.images.upload', $case->case_id) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label for="day_number" class="form-label">Development Day Target <span class="text-danger">*</span></label>
                            <select name="day_number" id="day_number" class="form-select" required>
                                @for($d = 1; $d <= $case->total_days; $d++)
                                    <option value="{{ $d }}" {{ $activeDay == $d ? 'selected' : '' }}>Day {{ $d }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="capture_time" class="form-label">Acquisition Time (24h) <span class="text-danger">*</span></label>
                            <input type="time"
                                   step="1"
                                   name="capture_time"
                                   id="capture_time"
                                   class="form-control mono"
                                   value="{{ date('H:i:s') }}"
                                   required>
                            <div class="form-text">
                                Used for 24h naming (e.g. <code>09-00-00.png</code>).
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="images" class="form-label">Select Image Files <span class="text-danger">*</span></label>
                        <input type="file"
                               name="images[]"
                               id="images"
                               class="form-control"
                               multiple
                               accept="image/jpeg,image/png,image/webp,image/tiff"
                               required>
                        <div class="form-text">
                            Supported scientific formats: JPG, JPEG, PNG, TIFF, WEBP (Max 20MB per file). Original files preserved without lossy compression.
                        </div>
                    </div>

                    <div class="alert-sci mb-0" style="background-color: var(--bg-off-white);">
                        <strong>Storage Architecture Policy:</strong><br>
                        Files are preserved verbatim in centralized private storage under <code>storage/app/private/embryos/{{ $case->case_id }}/Day_X/</code> with strict access control and audit logging.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sci-secondary" data-bs-dismiss="modal">CANCEL</button>
                    <button type="submit" class="btn btn-sci-primary px-4">
                        UPLOAD &amp; SAVE &rarr;
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
