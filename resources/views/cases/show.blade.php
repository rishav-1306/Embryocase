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

        <button type="button" class="btn btn-sm btn-sci-subtle" data-bs-toggle="modal" data-bs-target="#trashModal" title="View 24-Hour Quarantine Trash Bin">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -2px;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
            TRASH ({{ $trashedImages->count() }})
        </button>

        <button type="button" class="btn btn-sm btn-sci-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
            + UPLOAD IMAGES
        </button>
    </div>
</div>

@if(isset($isTodayMissing) && $isTodayMissing && isset($todayDay))
    <div class="alert-sci mb-4 p-3 d-flex flex-wrap justify-content-between align-items-center gap-2" style="background-color: #FFFBE6; border: 1px solid #000; border-left: 6px solid #D9381E;">
        <div>
            <div class="d-flex align-items-center gap-2">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D9381E" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h20.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <strong style="font-size: 14px; letter-spacing: 0.5px; color: #000;">SCHEDULE WARNING: TODAY'S ACQUISITION IMAGE IS NOT UPLOADED (DAY {{ $todayDay }})</strong>
            </div>
            <div class="text-dark mt-1" style="font-size: 13px;">
                According to the collection date ({{ $case->collection_date?->format('Y-m-d') }}), today is <strong>Day {{ $todayDay }}</strong> for Embryo #{{ $case->embryo_number }}. Please record and upload today's microscopy specimen.
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-sci-primary px-3" data-bs-toggle="modal" data-bs-target="#uploadModal">
            + UPLOAD DAY {{ $todayDay }} IMAGE NOW &rarr;
        </button>
    </div>
@endif

@if($viewMode === 'timeline')
    <!-- ==================== TIMELINE VIEW ==================== -->
    <div class="sci-card">
        <div class="sci-card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <span class="sci-card-title">DEVELOPMENT CHRONOLOGICAL TIMELINE</span>
                <span class="mono ms-2" style="font-size: 12px;">
                    {{ $case->images->count() }} RAW ORIGINAL(S) &bull; {{ $case->images->where('has_edit', true)->count() }} EDITED (_E)
                </span>
            </div>
            <!-- Timeline Gallery Filter -->
            <div class="btn-group btn-group-sm" role="group" id="timelineFilterGroup">
                <button type="button" class="btn btn-sci-primary active" data-filter="all">ALL (ORIGINAL + EDITED)</button>
                <button type="button" class="btn btn-sci-secondary" data-filter="original">ORIGINALS ONLY</button>
                <button type="button" class="btn btn-sci-secondary" data-filter="edited">EDITED ONLY</button>
            </div>
        </div>
        <div class="sci-card-body p-4">
            @php
                $imagesByDay = $case->images->groupBy('day_number');
            @endphp

            @for($d = 1; $d <= $case->total_days; $d++)
                @php 
                    $dayImgs = $imagesByDay->get($d, collect()); 
                    $editedCount = $dayImgs->where('has_edit', true)->count();
                @endphp
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
                        <div class="d-flex align-items-center gap-2">
                            <span class="mono text-muted" style="font-size: 12px;">
                                {{ $dayImgs->count() }} Original(s) &bull; {{ $editedCount }} Edited Version(s)
                            </span>
                            @if($case->total_days > 1)
                                <form method="POST" action="{{ route('cases.delete-day', $case->case_id) }}" class="d-inline m-0" onsubmit="return confirm('Are you sure you want to delete DAY {{ $d }} and all its images? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="day_number" value="{{ $d }}">
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 11px;" title="Delete Day {{ $d }}">
                                        &times; DELETE DAY {{ $d }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    @if($dayImgs->count() > 0)
                        <div class="embryo-gallery">
                            @foreach($dayImgs->sortBy('capture_time') as $img)
                                <!-- 1. ORIGINAL RAW IMAGE CARD (PERMANENT & PRESERVED) -->
                                <div class="embryo-card gallery-item-original">
                                    <div class="embryo-thumb-container">
                                        <img src="{{ route('images.serve', ['id' => $img->id, 'type' => 'original']) }}"
                                             alt="Original acquisition {{ $img->filename }}"
                                             class="embryo-thumb"
                                             loading="lazy">
                                        <span class="position-absolute top-0 start-0 m-2 badge-sci badge-sci-neutral" style="font-size: 10px; background-color: rgba(255,255,255,0.95); border: 1px solid #000;">
                                            ORIGINAL
                                        </span>
                                        @if($img->has_edit)
                                            <span class="position-absolute top-0 end-0 m-2 badge-sci badge-sci-active" style="font-size: 10px;" title="Edited version available at exact same timestamp">
                                                HAS EDIT &rarr;
                                            </span>
                                        @endif
                                    </div>
                                    <div class="embryo-card-info">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="embryo-time mono" title="Acquisition Time">{{ $img->capture_time }}</span>
                                                <form method="POST" action="{{ route('images.destroy', $img->id) }}" class="d-inline m-0 p-0" onsubmit="return confirm('Move embryo image {{ $img->filename }} to Trash? It will be retained for 24 hours and can be restored.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0 border-0" title="Delete Embryo Image (Move to 24h Trash)" style="line-height: 1;">
                                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-danger" style="vertical-align: -2px;">
                                                            <polyline points="3 6 5 6 21 6"></polyline>
                                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                            <line x1="10" y1="11" x2="10" y2="17"></line>
                                                            <line x1="14" y1="11" x2="14" y2="17"></line>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                            <span class="text-muted mono" style="font-size: 11px;">{{ $img->formatted_file_size }}</span>
                                        </div>
                                        <div class="text-muted mono mb-1" style="font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $img->filename }}">
                                            {{ $img->filename }}
                                        </div>
                                        <div class="text-secondary-label mb-2" style="font-size: 10px;">
                                            RAW UNTOUCHED &bull; {{ $img->width && $img->height ? "{$img->width}×{$img->height}px" : 'Microscopy' }}
                                        </div>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('images.view', ['id' => $img->id, 'mode' => 'original']) }}" class="btn btn-sm btn-sci-secondary w-50" style="font-size: 11px;">
                                                VIEW ORIGINAL
                                            </a>
                                            <a href="{{ route('images.editor', $img->id) }}" class="btn btn-sm btn-sci-primary w-50" style="font-size: 11px;">
                                                ANNOTATE
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. EDITED ANNOTATION CARD (SAME ACQUISITION TIMESTAMP) -->
                                @if($img->has_edit)
                                    <div class="embryo-card gallery-item-edited" style="border: 1px solid #000000;">
                                        <div class="embryo-thumb-container" style="background-color: #111;">
                                            <img src="{{ route('images.serve', ['id' => $img->id, 'type' => 'edited', 'v' => $img->annotation?->version]) }}"
                                                 alt="Edited acquisition {{ $img->edited_filename }}"
                                                 class="embryo-thumb"
                                                 loading="lazy">
                                            <span class="position-absolute top-0 start-0 m-2 badge-sci badge-sci-active" style="font-size: 10px;">
                                                EDITED (_E)
                                            </span>
                                            <span class="position-absolute top-0 end-0 m-2 badge-sci badge-sci-neutral" style="font-size: 10px; background-color: rgba(255,255,255,0.95); border: 1px solid #000;">
                                                v{{ $img->annotation?->version ?? 1 }}
                                            </span>
                                        </div>
                                        <div class="embryo-card-info" style="background-color: var(--bg-off-white);">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="embryo-time mono text-black fw-bold" title="Acquisition Time (Exact same as original)">{{ $img->capture_time }}</span>
                                                    <form method="POST" action="{{ route('images.destroy-edit', $img->id) }}" class="d-inline m-0 p-0" onsubmit="return confirm('Delete this edited annotation layer (_E)? The raw original embryo image will remain preserved.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0 border-0" title="Delete Edited Version (Move to 24h Trash)" style="line-height: 1;">
                                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-danger" style="vertical-align: -2px;">
                                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                                <span class="badge-sci badge-sci-neutral mono" style="font-size: 9px; padding: 2px 4px;">SAME TIME</span>
                                            </div>
                                            <div class="text-muted mono mb-1" style="font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $img->edited_filename }}">
                                                {{ $img->edited_filename }}
                                            </div>
                                            <div class="text-secondary-label mb-2" style="font-size: 10px;">
                                                CLINICAL ANNOTATION LAYER
                                            </div>
                                            <div class="d-flex gap-1 mb-1">
                                                <a href="{{ route('images.view', ['id' => $img->id, 'mode' => 'edited']) }}" class="btn btn-sm btn-sci-secondary w-50" style="font-size: 11px;">
                                                    VIEW EDITED
                                                </a>
                                                <a href="{{ route('images.editor', $img->id) }}" class="btn btn-sm btn-sci-primary w-50" style="font-size: 11px;">
                                                    RE-ANNOTATE
                                                </a>
                                            </div>
                                            <a href="{{ route('cases.compare', ['caseId' => $case->case_id, 'image_a' => $img->id, 'type_a' => 'original', 'image_b' => $img->id, 'type_b' => 'edited']) }}" class="btn btn-sm btn-sci-subtle w-100 py-0" style="font-size: 10px; text-align: center;">
                                                COMPARE WITH ORIGINAL &rarr;
                                            </a>
                                        </div>
                                    </div>
                                @endif
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
                    @if($case->total_days > 1)
                        <form method="POST" action="{{ route('cases.delete-day', $case->case_id) }}" class="m-0 align-self-center ps-1" onsubmit="return confirm('Are you sure you want to delete DAY {{ $activeDay }} and all its associated embryo images? This action cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="day_number" value="{{ $activeDay }}">
                            <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Delete Day {{ $activeDay }}">
                                &times; DELETE DAY {{ $activeDay }}
                            </button>
                        </form>
                    @endif
                </div>

                <!-- Active Day Action Bar -->
                @php
                    $rawOriginalCount = $dayImages->count();
                    $editedVersionCount = $dayImages->where('has_edit', true)->count();
                @endphp
                <div class="p-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2" style="background-color: var(--bg-white); border-color: var(--border-grey) !important;">
                    <div>
                        <h3 class="mb-0">DAY {{ $activeDay }} REPOSITORY</h3>
                        <div class="text-muted" style="font-size: 12px;">
                            <span class="mono fw-bold text-black">{{ $rawOriginalCount }}</span> Original Raw Specimen(s) &bull;
                            <span class="mono fw-bold text-black">{{ $editedVersionCount }}</span> Edited Annotation(s) (_E) &bull;
                            Path: <code class="mono" style="font-size: 11px;">storage/app/private/embryos/{{ $case->case_id }}/Day_{{ $activeDay }}/</code>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <!-- Filter Toggle -->
                        <div class="btn-group btn-group-sm" role="group" id="folderFilterGroup">
                            <button type="button" class="btn btn-sci-primary active" data-filter="all">
                                ALL ({{ $rawOriginalCount + $editedVersionCount }})
                            </button>
                            <button type="button" class="btn btn-sci-secondary" data-filter="original">
                                ORIGINALS ({{ $rawOriginalCount }})
                            </button>
                            <button type="button" class="btn btn-sci-secondary" data-filter="edited">
                                EDITED ({{ $editedVersionCount }})
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-sci-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            + UPLOAD TO DAY {{ $activeDay }}
                        </button>
                        @if($case->total_days > 1)
                            <form method="POST" action="{{ route('cases.delete-day', $case->case_id) }}" class="m-0 d-inline" onsubmit="return confirm('Are you sure you want to delete DAY {{ $activeDay }} and all its associated embryo images? This action cannot be undone.');">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="day_number" value="{{ $activeDay }}">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Day {{ $activeDay }}">
                                    &times; DELETE DAY {{ $activeDay }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- Image Gallery Grid -->
                <div class="sci-card-body p-3">
                    @if($dayImages->count() > 0)
                        <div class="embryo-gallery">
                            @foreach($dayImages->sortBy('capture_time') as $img)
                                <!-- 1. ORIGINAL RAW ACQUISITION CARD (PERMANENT & PRESERVED) -->
                                <div class="embryo-card gallery-item-original">
                                    <div class="embryo-thumb-container">
                                        <img src="{{ route('images.serve', ['id' => $img->id, 'type' => 'original']) }}"
                                             alt="Original acquisition {{ $img->filename }}"
                                             class="embryo-thumb"
                                             loading="lazy">
                                        <span class="position-absolute top-0 start-0 m-2 badge-sci badge-sci-neutral" style="font-size: 10px; background-color: rgba(255,255,255,0.95); border: 1px solid #000;">
                                            ORIGINAL
                                        </span>
                                        @if($img->has_edit)
                                            <span class="position-absolute top-0 end-0 m-2 badge-sci badge-sci-active" style="font-size: 10px;" title="Edited version preserved at exact same acquisition timestamp">
                                                HAS EDIT &rarr;
                                            </span>
                                        @endif
                                    </div>
                                    <div class="embryo-card-info">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="embryo-time mono" title="Acquisition Time">{{ $img->capture_time }}</span>
                                                <form method="POST" action="{{ route('images.destroy', $img->id) }}" class="d-inline m-0 p-0" onsubmit="return confirm('Move embryo image {{ $img->filename }} to Trash? It will be retained for 24 hours and can be restored.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0 border-0" title="Delete Embryo Image (Move to 24h Trash)" style="line-height: 1;">
                                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-danger" style="vertical-align: -2px;">
                                                            <polyline points="3 6 5 6 21 6"></polyline>
                                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                            <line x1="10" y1="11" x2="10" y2="17"></line>
                                                            <line x1="14" y1="11" x2="14" y2="17"></line>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                            <span class="text-muted mono" style="font-size: 11px;">{{ $img->formatted_file_size }}</span>
                                        </div>
                                        <div class="text-muted mono mb-1" style="font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $img->filename }}">
                                            {{ $img->filename }}
                                        </div>
                                        <div class="text-secondary-label mb-2" style="font-size: 10px;">
                                            RAW UNTOUCHED &bull; {{ $img->width && $img->height ? "{$img->width}×{$img->height}px" : 'Microscopy' }}
                                        </div>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('images.view', ['id' => $img->id, 'mode' => 'original']) }}" class="btn btn-sm btn-sci-secondary w-50" style="font-size: 11px;">
                                                VIEW ORIGINAL
                                            </a>
                                            <a href="{{ route('images.editor', $img->id) }}" class="btn btn-sm btn-sci-primary w-50" style="font-size: 11px;">
                                                ANNOTATE
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. EDITED ANNOTATION CARD (EXACT SAME ACQUISITION TIMESTAMP) -->
                                @if($img->has_edit)
                                    <div class="embryo-card gallery-item-edited" style="border: 1px solid #000000;">
                                        <div class="embryo-thumb-container" style="background-color: #111;">
                                            <img src="{{ route('images.serve', ['id' => $img->id, 'type' => 'edited', 'v' => $img->annotation?->version]) }}"
                                                 alt="Edited acquisition {{ $img->edited_filename }}"
                                                 class="embryo-thumb"
                                                 loading="lazy">
                                            <span class="position-absolute top-0 start-0 m-2 badge-sci badge-sci-active" style="font-size: 10px;">
                                                EDITED (_E)
                                            </span>
                                            <span class="position-absolute top-0 end-0 m-2 badge-sci badge-sci-neutral" style="font-size: 10px; background-color: rgba(255,255,255,0.95); border: 1px solid #000;">
                                                v{{ $img->annotation?->version ?? 1 }}
                                            </span>
                                        </div>
                                        <div class="embryo-card-info" style="background-color: var(--bg-off-white);">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="embryo-time mono text-black fw-bold" title="Acquisition Time (Identical to original)">{{ $img->capture_time }}</span>
                                                    <form method="POST" action="{{ route('images.destroy-edit', $img->id) }}" class="d-inline m-0 p-0" onsubmit="return confirm('Delete this edited annotation layer (_E)? The raw original embryo image will remain preserved.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0 border-0" title="Delete Edited Version (Move to 24h Trash)" style="line-height: 1;">
                                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-danger" style="vertical-align: -2px;">
                                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                                                <line x1="14" y1="11" x2="14" y2="17"></line>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                                <span class="badge-sci badge-sci-neutral mono" style="font-size: 9px; padding: 2px 4px;">SAME TIME</span>
                                            </div>
                                            <div class="text-muted mono mb-1" style="font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $img->edited_filename }}">
                                                {{ $img->edited_filename }}
                                            </div>
                                            <div class="text-secondary-label mb-2" style="font-size: 10px;">
                                                CLINICAL ANNOTATION LAYER
                                            </div>
                                            <div class="d-flex gap-1 mb-1">
                                                <a href="{{ route('images.view', ['id' => $img->id, 'mode' => 'edited']) }}" class="btn btn-sm btn-sci-secondary w-50" style="font-size: 11px;">
                                                    VIEW EDITED
                                                </a>
                                                <a href="{{ route('images.editor', $img->id) }}" class="btn btn-sm btn-sci-primary w-50" style="font-size: 11px;">
                                                    RE-ANNOTATE
                                                </a>
                                            </div>
                                            <a href="{{ route('cases.compare', ['caseId' => $case->case_id, 'image_a' => $img->id, 'type_a' => 'original', 'image_b' => $img->id, 'type_b' => 'edited']) }}" class="btn btn-sm btn-sci-subtle w-100 py-0" style="font-size: 10px; text-align: center;">
                                                COMPARE WITH ORIGINAL &rarr;
                                            </a>
                                        </div>
                                    </div>
                                @endif
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

<!-- ==================== 24-HOUR TRASH BIN MODAL ==================== -->
<div class="modal fade" id="trashModal" tabindex="-1" aria-labelledby="trashModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    <h5 class="modal-title mb-0" id="trashModalLabel" style="font-size: 15px; font-weight: 700; text-transform: uppercase;">
                        24-Hour Embryo Trash Bin &bull; {{ $case->case_id }}
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert-sci mb-3 py-2 px-3" style="font-size: 12px; background-color: var(--bg-off-white);">
                    <strong>Laboratory Data Retention Policy:</strong> Deleted embryo images are kept in this secure quarantine buffer for <strong>24 hours</strong>. During this window, both Embryologists and Admins can restore them. After 24 hours, expired items are permanently purged from disk storage.
                </div>

                @if($trashedImages->count() > 0)
                    <div class="table-responsive">
                        <table class="table-sci mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Preview</th>
                                    <th>Image / Timestamp</th>
                                    <th>Day</th>
                                    <th>Deleted At</th>
                                    <th>Retention</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($trashedImages as $tImg)
                                    <tr>
                                        <td>
                                            <img src="{{ route('images.serve', ['id' => $tImg->id, 'type' => 'original']) }}"
                                                 alt="{{ $tImg->filename }}"
                                                 style="width: 60px; height: 60px; object-fit: cover; border: 1px solid var(--border-grey);">
                                        </td>
                                        <td>
                                            <div class="mono fw-bold" style="font-size: 12px;">{{ $tImg->filename }}</div>
                                            <div class="text-muted mono" style="font-size: 11px;">Acquired: {{ $tImg->capture_time }}</div>
                                            <div class="text-muted" style="font-size: 10px;">{{ $tImg->formatted_file_size }}</div>
                                        </td>
                                        <td>
                                            <span class="badge-sci badge-sci-neutral" style="font-size: 11px;">Day {{ $tImg->day_number }}</span>
                                        </td>
                                        <td>
                                            <div class="mono" style="font-size: 11px;">{{ $tImg->deleted_at?->format('Y-m-d H:i') }}</div>
                                            <div class="text-muted" style="font-size: 10px;">{{ $tImg->deleted_at?->diffForHumans() }}</div>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning text-dark mono" style="font-size: 10px;">
                                                {{ $tImg->trash_hours_remaining }}h remaining
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-1">
                                                <form method="POST" action="{{ route('images.restore', $tImg->id) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-sci-primary py-1 px-2" style="font-size: 11px;">
                                                        RESTORE
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('images.force-delete', $tImg->id) }}" class="d-inline" onsubmit="return confirm('Permanently purge {{ $tImg->filename }} from disk immediately?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" style="font-size: 11px;">
                                                        PURGE NOW
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mb-2 text-muted">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        <div class="fw-bold mb-1" style="font-size: 13px;">TRASH BIN IS EMPTY</div>
                        <p class="mb-0" style="font-size: 12px;">No embryo images deleted within the last 24 hours for this case.</p>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sci-secondary" data-bs-dismiss="modal">CLOSE</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function setupFilter(groupId) {
        const group = document.getElementById(groupId);
        if (!group) return;

        const buttons = group.querySelectorAll('button[data-filter]');
        buttons.forEach(btn => {
            btn.addEventListener('click', function() {
                buttons.forEach(b => {
                    b.classList.remove('btn-sci-primary', 'active');
                    b.classList.add('btn-sci-secondary');
                });
                this.classList.remove('btn-sci-secondary');
                this.classList.add('btn-sci-primary', 'active');

                const filter = this.getAttribute('data-filter');
                const originals = document.querySelectorAll('.gallery-item-original');
                const editeds = document.querySelectorAll('.gallery-item-edited');

                originals.forEach(el => {
                    el.style.display = (filter === 'all' || filter === 'original') ? 'flex' : 'none';
                });
                editeds.forEach(el => {
                    el.style.display = (filter === 'all' || filter === 'edited') ? 'flex' : 'none';
                });
            });
        });
    }

    setupFilter('folderFilterGroup');
    setupFilter('timelineFilterGroup');
});
</script>
@endsection

