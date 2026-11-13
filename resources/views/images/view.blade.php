@extends('layouts.app')

@section('title', 'Scientific Image Viewer: ' . $image->filename)

@section('content')
@php
    $requestedMode = request('mode');
    $initialMode = ($requestedMode === 'original' || !$image->has_edit) ? 'original' : 'edited';
@endphp

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 pb-2 border-bottom" style="border-color: var(--border-grey) !important;">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="text-secondary-label">SCIENTIFIC IMAGE VIEWER</span>
            <span class="mono fw-bold">{{ $image->embryoCase->case_id }}</span>
            <span class="badge-sci badge-sci-neutral">DAY {{ $image->day_number }}</span>
            @if($image->has_edit)
                @if($initialMode === 'edited')
                    <span class="badge-sci badge-sci-active" id="statusBadge">EDITED PREVIEW (_E)</span>
                @else
                    <span class="badge-sci badge-sci-neutral" id="statusBadge">ORIGINAL ACQUISITION</span>
                @endif
            @else
                <span class="badge-sci badge-sci-neutral" id="statusBadge">ORIGINAL ACQUISITION (UNEDITED)</span>
            @endif
        </div>
        <h1 class="h3 mb-0 mono" id="displayedFilename">{{ $initialMode === 'edited' && $image->has_edit ? $image->edited_filename : $image->filename }}</h1>
    </div>

    <div class="mt-2 mt-md-0 d-flex gap-2">
        <a href="{{ route('cases.show', ['caseId' => $image->embryoCase->case_id, 'day' => $image->day_number]) }}" class="btn btn-sm btn-sci-secondary">
            &larr; BACK TO CASE
        </a>
        @if($image->has_edit)
            <a href="{{ route('cases.compare', ['caseId' => $image->embryoCase->case_id, 'image_a' => $image->id, 'type_a' => 'original', 'image_b' => $image->id, 'type_b' => 'edited']) }}" class="btn btn-sm btn-sci-secondary">
                COMPARE ORIGINAL VS EDITED
            </a>
        @else
            <a href="{{ route('cases.compare', ['caseId' => $image->embryoCase->case_id, 'image_a' => $image->id]) }}" class="btn btn-sm btn-sci-secondary">
                COMPARE
            </a>
        @endif
        <a href="{{ route('images.editor', $image->id) }}" class="btn btn-sm btn-sci-primary">
            {{ $image->has_edit ? 'RE-ANNOTATE IN EDITOR &rarr;' : 'OPEN ANNOTATION EDITOR &rarr;' }}
        </a>
        <form method="POST" action="{{ route('images.destroy', $image->id) }}" class="d-inline m-0" onsubmit="return confirm('Are you sure you want to permanently delete this embryo image ({{ $image->filename }})? This action cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger">
                &times; DELETE EMBRYO
            </button>
        </form>
    </div>
</div>

<!-- Scientific Viewer Controls Toolbar -->
<div class="viewer-toolbar mb-2">
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-sci-secondary" id="zoomInBtn" title="Zoom In (+)">
            ZOOM IN (+)
        </button>
        <button type="button" class="btn btn-sm btn-sci-secondary" id="zoomOutBtn" title="Zoom Out (-)">
            ZOOM OUT (-)
        </button>
        <button type="button" class="btn btn-sm btn-sci-subtle" id="resetZoomBtn" title="Reset Scale (100%)">
            RESET VIEW (100%)
        </button>
        <button type="button" class="btn btn-sm btn-sci-subtle" id="fullscreenBtn" title="Fullscreen View">
            FULLSCREEN
        </button>
        <span class="mono ms-2" id="zoomLevelText" style="font-size: 12px;">100%</span>
    </div>

    <div class="d-flex align-items-center gap-2">
        @if($image->has_edit)
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm {{ $initialMode === 'original' ? 'btn-sci-primary active' : 'btn-sci-secondary' }}" id="showOriginalBtn">
                    ORIGINAL
                </button>
                <button type="button" class="btn btn-sm {{ $initialMode === 'edited' ? 'btn-sci-primary active' : 'btn-sci-secondary' }}" id="showEditedBtn">
                    EDITED (_E)
                </button>
            </div>
        @else
            <span class="badge-sci badge-sci-neutral">ORIGINAL ONLY (NO EDITS)</span>
        @endif
    </div>
</div>

<!-- Main Viewing Stage -->
<div class="viewer-workspace" id="viewerStage">
    <img src="{{ route('images.serve', ['id' => $image->id, 'type' => $initialMode, 'v' => $image->annotation?->version]) }}"
         alt="Embryo acquisition {{ $image->filename }}"
         id="targetImage"
         class="viewer-canvas"
         draggable="false">
</div>

<!-- Scientific Metadata Panel -->
<div class="sci-card mt-3">
    <div class="sci-card-header">
        <span class="sci-card-title">IMAGE SPECIFICATIONS &amp; AUDIT METADATA</span>
        <span class="mono" style="font-size: 11px;">NON-DESTRUCTIVE CLINICAL STORAGE POLICY</span>
    </div>
    <div class="sci-card-body p-0">
        <div class="row g-0">
            <div class="col-12 col-md-6 border-end" style="border-color: var(--border-grey) !important;">
                <table class="table-sci mb-0">
                    <tr>
                        <th style="width: 42%;">Embryo Case ID</th>
                        <td class="mono fw-bold">{{ $image->embryoCase->case_id }}</td>
                    </tr>
                    <tr>
                        <th>Embryo Number</th>
                        <td class="mono">{{ $image->embryoCase->embryo_number }}</td>
                    </tr>
                    <tr>
                        <th>Development Day</th>
                        <td class="mono">Day {{ $image->day_number }}</td>
                    </tr>
                    <tr>
                        <th>Image Acquisition Time</th>
                        <td class="mono fw-bold text-black">
                            {{ $image->capture_time }} (24h UTC)
                            <div class="text-muted" style="font-size: 10px; font-weight: normal;">
                                &bull; Maintained identically across Original &amp; Edited versions
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>Capture Date</th>
                        <td class="mono">{{ $image->capture_date?->format('Y-m-d') ?? 'N/A' }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-12 col-md-6">
                <table class="table-sci mb-0">
                    <tr>
                        <th style="width: 42%;">Original Raw File</th>
                        <td class="mono text-black fw-bold">
                            {{ $image->filename }}
                            <div class="text-muted" style="font-size: 10px; font-weight: normal;">
                                Status: Permanent &amp; Untouched
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>Edited Layer File</th>
                        <td class="mono">
                            @if($image->has_edit)
                                <span class="text-black fw-bold">{{ $image->edited_filename }}</span>
                                <span class="badge-sci badge-sci-active" style="font-size: 9px;">ACTIVE</span>
                            @else
                                <span class="text-muted">None (Original unannotated)</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Image Dimensions</th>
                        <td class="mono">{{ $image->width ? "{$image->width} × {$image->height} px" : 'Raw Specimen' }}</td>
                    </tr>
                    <tr>
                        <th>Original File Size</th>
                        <td class="mono">{{ $image->formatted_file_size }} ({{ number_format($image->file_size) }} bytes)</td>
                    </tr>
                    <tr>
                        <th>Uploaded By</th>
                        <td>{{ $image->user?->name ?? 'Laboratory User' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        @if($image->has_edit && $image->annotation)
            <div class="p-3 border-top" style="border-color: var(--border-grey) !important; background-color: var(--bg-off-white);">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <span class="text-secondary-label">ANNOTATION VERSION AUDIT:</span>
                        <span class="mono fw-bold ms-1">Version {{ $image->annotation->version }}</span> &bull;
                        <span class="text-muted">Annotated by {{ $image->annotation->user?->name ?? 'Embryologist' }} on {{ $image->annotation->updated_at?->format('Y-m-d H:i') }}</span> &bull;
                        <span class="mono" style="font-size: 11px;">Vector JSON: <code>{{ basename($image->annotation_json_path ?? '') }}</code></span>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('cases.compare', ['caseId' => $image->embryoCase->case_id, 'image_a' => $image->id, 'type_a' => 'original', 'image_b' => $image->id, 'type_b' => 'edited']) }}" class="btn btn-sm btn-sci-secondary">
                            COMPARE SIDE-BY-SIDE
                        </a>
                        <a href="{{ route('images.editor', $image->id) }}" class="btn btn-sm btn-sci-primary">
                            MODIFY ANNOTATIONS
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const stage = document.getElementById('viewerStage');
    const img = document.getElementById('targetImage');
    const zoomLevelText = document.getElementById('zoomLevelText');
    const zoomInBtn = document.getElementById('zoomInBtn');
    const zoomOutBtn = document.getElementById('zoomOutBtn');
    const resetZoomBtn = document.getElementById('resetZoomBtn');
    const fullscreenBtn = document.getElementById('fullscreenBtn');

    const showOriginalBtn = document.getElementById('showOriginalBtn');
    const showEditedBtn = document.getElementById('showEditedBtn');
    const statusBadge = document.getElementById('statusBadge');

    const originalUrl = "{{ route('images.serve', ['id' => $image->id, 'type' => 'original']) }}";
    const editedUrl = "{{ route('images.serve', ['id' => $image->id, 'type' => 'edited', 't' => time()]) }}";

    let scale = 1.0;
    let panX = 0;
    let panY = 0;
    let isPanning = false;
    let startX = 0;
    let startY = 0;

    function updateTransform() {
        img.style.transform = `translate(${panX}px, ${panY}px) scale(${scale})`;
        zoomLevelText.innerText = Math.round(scale * 100) + '%';
    }

    zoomInBtn.addEventListener('click', function() {
        scale = Math.min(scale + 0.25, 5.0);
        updateTransform();
    });

    zoomOutBtn.addEventListener('click', function() {
        scale = Math.max(scale - 0.25, 0.25);
        updateTransform();
    });

    resetZoomBtn.addEventListener('click', function() {
        scale = 1.0;
        panX = 0;
        panY = 0;
        updateTransform();
    });

    // Mouse Pan Controls
    stage.addEventListener('mousedown', function(e) {
        if (e.button === 0) { // left click
            isPanning = true;
            startX = e.clientX - panX;
            startY = e.clientY - panY;
            stage.style.cursor = 'grabbing';
        }
    });

    window.addEventListener('mousemove', function(e) {
        if (!isPanning) return;
        panX = e.clientX - startX;
        panY = e.clientY - startY;
        updateTransform();
    });

    window.addEventListener('mouseup', function() {
        isPanning = false;
        stage.style.cursor = 'grab';
    });

    stage.style.cursor = 'grab';

    // Scroll wheel zoom
    stage.addEventListener('wheel', function(e) {
        e.preventDefault();
        if (e.deltaY < 0) {
            scale = Math.min(scale + 0.15, 5.0);
        } else {
            scale = Math.max(scale - 0.15, 0.25);
        }
        updateTransform();
    }, { passive: false });

    // Fullscreen toggle
    fullscreenBtn.addEventListener('click', function() {
        if (!document.fullscreenElement) {
            stage.requestFullscreen().catch(err => alert("Fullscreen error: " + err.message));
        } else {
            document.exitFullscreen();
        }
    });

    // Toggle Original vs Edited
    if (showOriginalBtn && showEditedBtn) {
        const originalFilename = "{{ $image->filename }}";
        const editedFilename = "{{ $image->edited_filename ?? $image->filename }}";
        const displayedFilename = document.getElementById('displayedFilename');

        showOriginalBtn.addEventListener('click', function() {
            img.src = originalUrl;
            showOriginalBtn.classList.add('btn-sci-primary', 'active');
            showOriginalBtn.classList.remove('btn-sci-secondary');
            showEditedBtn.classList.add('btn-sci-secondary');
            showEditedBtn.classList.remove('btn-sci-primary', 'active');
            statusBadge.innerText = 'ORIGINAL ACQUISITION';
            statusBadge.className = 'badge-sci badge-sci-neutral';
            if (displayedFilename) displayedFilename.innerText = originalFilename;
        });

        showEditedBtn.addEventListener('click', function() {
            img.src = editedUrl;
            showEditedBtn.classList.add('btn-sci-primary', 'active');
            showEditedBtn.classList.remove('btn-sci-secondary');
            showOriginalBtn.classList.add('btn-sci-secondary');
            showOriginalBtn.classList.remove('btn-sci-primary', 'active');
            statusBadge.innerText = 'EDITED PREVIEW (_E)';
            statusBadge.className = 'badge-sci badge-sci-active';
            if (displayedFilename) displayedFilename.innerText = editedFilename;
        });
    }
});
</script>
@endsection

