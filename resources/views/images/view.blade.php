@extends('layouts.app')

@section('title', 'Scientific Image Viewer: ' . $image->filename)

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 pb-2 border-bottom" style="border-color: var(--border-grey) !important;">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="text-secondary-label">SCIENTIFIC IMAGE VIEWER</span>
            <span class="mono fw-bold">{{ $image->embryoCase->case_id }}</span>
            <span class="badge-sci badge-sci-neutral">DAY {{ $image->day_number }}</span>
            @if($image->has_edit)
                <span class="badge-sci badge-sci-active" id="statusBadge">EDITED PREVIEW</span>
            @else
                <span class="badge-sci badge-sci-neutral" id="statusBadge">ORIGINAL ACQUISITION</span>
            @endif
        </div>
        <h1 class="h3 mb-0 mono">{{ $image->filename }}</h1>
    </div>

    <div class="mt-2 mt-md-0 d-flex gap-2">
        <a href="{{ route('cases.show', ['caseId' => $image->embryoCase->case_id, 'day' => $image->day_number]) }}" class="btn btn-sm btn-sci-secondary">
            &larr; BACK TO CASE
        </a>
        <a href="{{ route('cases.compare', ['caseId' => $image->embryoCase->case_id, 'image_a' => $image->id]) }}" class="btn btn-sm btn-sci-secondary">
            COMPARE
        </a>
        <a href="{{ route('images.editor', $image->id) }}" class="btn btn-sm btn-sci-primary">
            OPEN ANNOTATION EDITOR &rarr;
        </a>
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
                <button type="button" class="btn btn-sm btn-sci-secondary" id="showOriginalBtn">
                    ORIGINAL
                </button>
                <button type="button" class="btn btn-sm btn-sci-primary" id="showEditedBtn">
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
    <img src="{{ route('images.serve', ['id' => $image->id, 'type' => $image->has_edit ? 'edited' : 'original']) }}"
         alt="Embryo acquisition {{ $image->filename }}"
         id="targetImage"
         class="viewer-canvas"
         draggable="false">
</div>

<!-- Scientific Metadata Panel -->
<div class="sci-card mt-3">
    <div class="sci-card-header">
        <span class="sci-card-title">IMAGE SPECIFICATIONS &amp; AUDIT METADATA</span>
        <span class="mono" style="font-size: 11px;">SHA-256 NON-DESTRUCTIVE TRACEABILITY</span>
    </div>
    <div class="sci-card-body p-0">
        <div class="row g-0">
            <div class="col-12 col-md-6 border-end" style="border-color: var(--border-grey) !important;">
                <table class="table-sci mb-0">
                    <tr>
                        <th style="width: 40%;">Embryo Case ID</th>
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
                        <td class="mono fw-bold">{{ $image->capture_time }} (24h UTC)</td>
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
                        <th style="width: 40%;">Original Filename</th>
                        <td class="mono">{{ $image->original_name }}</td>
                    </tr>
                    <tr>
                        <th>Image Dimensions</th>
                        <td class="mono">{{ $image->width ? "{$image->width} × {$image->height} px" : 'Raw Vector / Unspecified' }}</td>
                    </tr>
                    <tr>
                        <th>File Size</th>
                        <td class="mono">{{ $image->formatted_file_size }} ({{ number_format($image->file_size) }} bytes)</td>
                    </tr>
                    <tr>
                        <th>Acquired By</th>
                        <td>{{ $image->user?->name ?? 'Laboratory User' }}</td>
                    </tr>
                    <tr>
                        <th>Upload Timestamp</th>
                        <td class="mono">{{ $image->created_at?->format('Y-m-d H:i:s') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        @if($image->has_edit && $image->annotation)
            <div class="p-3 border-top" style="border-color: var(--border-grey) !important; background-color: var(--bg-off-white);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-secondary-label">ANNOTATION VERSION STATUS:</span>
                        <span class="mono fw-bold ms-1">Version {{ $image->annotation->version }}</span> &bull;
                        <span class="text-muted">Edited by {{ $image->annotation->user?->name ?? 'Embryologist' }} on {{ $image->annotation->updated_at?->format('Y-m-d H:i') }}</span>
                    </div>
                    <a href="{{ route('images.editor', $image->id) }}" class="btn btn-sm btn-sci-secondary">
                        MODIFY ANNOTATIONS
                    </a>
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
        showOriginalBtn.addEventListener('click', function() {
            img.src = originalUrl;
            showOriginalBtn.classList.add('btn-sci-primary');
            showOriginalBtn.classList.remove('btn-sci-secondary');
            showEditedBtn.classList.add('btn-sci-secondary');
            showEditedBtn.classList.remove('btn-sci-primary');
            statusBadge.innerText = 'ORIGINAL ACQUISITION';
            statusBadge.className = 'badge-sci badge-sci-neutral';
        });

        showEditedBtn.addEventListener('click', function() {
            img.src = editedUrl;
            showEditedBtn.classList.add('btn-sci-primary');
            showEditedBtn.classList.remove('btn-sci-secondary');
            showOriginalBtn.classList.add('btn-sci-secondary');
            showOriginalBtn.classList.remove('btn-sci-primary');
            statusBadge.innerText = 'EDITED PREVIEW';
            statusBadge.className = 'badge-sci badge-sci-active';
        });
    }
});
</script>
@endsection
