@extends('layouts.app')

@section('title', 'Side-by-Side Comparison: ' . $case->case_id)

@section('styles')
<style>
    .comparison-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        height: 68vh;
        min-height: 480px;
        margin-bottom: 1.5rem;
    }
    @media (max-width: 991px) {
        .comparison-container {
            grid-template-columns: 1fr;
            height: auto;
        }
    }
    .compare-pane {
        border: 1px solid var(--border-grey);
        background-color: var(--bg-white);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .compare-pane-header {
        background-color: var(--bg-off-white);
        border-bottom: 1px solid var(--border-grey);
        padding: 0.5rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .compare-stage {
        flex: 1;
        background-color: #0d0d0d;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
        cursor: grab;
        min-height: 380px;
    }
    .compare-img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        transform-origin: center center;
        user-select: none;
    }
</style>
@endsection

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 pb-2 border-bottom" style="border-color: var(--border-grey) !important;">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="text-secondary-label">SCIENTIFIC COMPARISON WORKSPACE</span>
            <span class="mono fw-bold">{{ $case->case_id }}</span>
            <span class="badge-sci badge-sci-neutral">EMBRYO #{{ $case->embryo_number }}</span>
        </div>
        <h1 class="h3 mb-0">Dual-Viewport Comparative Analysis</h1>
    </div>
    <div class="mt-2 mt-md-0 d-flex gap-2">
        <a href="{{ route('cases.show', $case->case_id) }}" class="btn btn-sm btn-sci-secondary">
            &larr; BACK TO CASE
        </a>
    </div>
</div>

<!-- Comparison Selection Form -->
<div class="sci-card mb-3">
    <div class="sci-card-body p-3">
        <form method="GET" action="{{ route('cases.compare', $case->case_id) }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-4">
                <label class="form-label mb-1" style="font-size: 11px;">PRIMARY VIEWPORT (LEFT)</label>
                <div class="input-group input-group-sm">
                    <select name="image_a" class="form-select">
                        @foreach($allImages as $im)
                            <option value="{{ $im->id }}" {{ $imageA?->id == $im->id ? 'selected' : '' }}>
                                Day {{ $im->day_number }} &bull; {{ $im->capture_time }} ({{ $im->filename }})
                            </option>
                        @endforeach
                    </select>
                    <select name="type_a" class="form-select" style="max-width: 110px;">
                        <option value="original" {{ $typeA === 'original' ? 'selected' : '' }}>Original</option>
                        @if($imageA?->has_edit)
                            <option value="edited" {{ $typeA === 'edited' ? 'selected' : '' }}>Edited (_E)</option>
                        @endif
                    </select>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label mb-1" style="font-size: 11px;">SECONDARY VIEWPORT (RIGHT)</label>
                <div class="input-group input-group-sm">
                    <select name="image_b" class="form-select">
                        @foreach($allImages as $im)
                            <option value="{{ $im->id }}" {{ $imageB?->id == $im->id ? 'selected' : '' }}>
                                Day {{ $im->day_number }} &bull; {{ $im->capture_time }} ({{ $im->filename }})
                            </option>
                        @endforeach
                    </select>
                    <select name="type_b" class="form-select" style="max-width: 110px;">
                        <option value="original" {{ $typeB === 'original' ? 'selected' : '' }}>Original</option>
                        @if($imageB?->has_edit)
                            <option value="edited" {{ $typeB === 'edited' ? 'selected' : '' }}>Edited (_E)</option>
                        @endif
                    </select>
                </div>
            </div>

            <div class="col-12 col-md-4 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-sm btn-sci-primary w-50">
                    LOAD COMPARISON
                </button>
                <button type="button" class="btn btn-sm btn-sci-secondary w-50" id="syncZoomBtn">
                    SYNC ZOOM (100%)
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Dual Viewport Stages -->
<div class="comparison-container">
    <!-- PANE A (LEFT) -->
    <div class="compare-pane">
        <div class="compare-pane-header">
            <div>
                <strong style="font-size: 12px;">VIEWPORT A</strong>
                @if($imageA)
                    <span class="mono ms-2" style="font-size: 12px;">
                        Day {{ $imageA->day_number }} &bull; {{ $imageA->capture_time }} &bull;
                        {{ strtoupper($typeA) }}
                    </span>
                @endif
            </div>
            <div class="d-flex align-items-center gap-1">
                <button type="button" class="btn btn-sm btn-sci-subtle py-0 px-2" id="zoomOutA">-</button>
                <span class="mono" id="zoomTextA" style="font-size: 11px;">100%</span>
                <button type="button" class="btn btn-sm btn-sci-subtle py-0 px-2" id="zoomInA">+</button>
                <button type="button" class="btn btn-sm btn-sci-subtle py-0 px-2" id="resetA">RESET</button>
            </div>
        </div>
        <div class="compare-stage" id="stageA">
            @if($imageA)
                <img src="{{ route('images.serve', ['id' => $imageA->id, 'type' => $typeA]) }}"
                     alt="Viewport A"
                     id="imgA"
                     class="compare-img"
                     draggable="false">
            @else
                <span class="text-muted">No image selected for Viewport A</span>
            @endif
        </div>
    </div>

    <!-- PANE B (RIGHT) -->
    <div class="compare-pane">
        <div class="compare-pane-header">
            <div>
                <strong style="font-size: 12px;">VIEWPORT B</strong>
                @if($imageB)
                    <span class="mono ms-2" style="font-size: 12px;">
                        Day {{ $imageB->day_number }} &bull; {{ $imageB->capture_time }} &bull;
                        {{ strtoupper($typeB) }}
                    </span>
                @endif
            </div>
            <div class="d-flex align-items-center gap-1">
                <button type="button" class="btn btn-sm btn-sci-subtle py-0 px-2" id="zoomOutB">-</button>
                <span class="mono" id="zoomTextB" style="font-size: 11px;">100%</span>
                <button type="button" class="btn btn-sm btn-sci-subtle py-0 px-2" id="zoomInB">+</button>
                <button type="button" class="btn btn-sm btn-sci-subtle py-0 px-2" id="resetB">RESET</button>
            </div>
        </div>
        <div class="compare-stage" id="stageB">
            @if($imageB)
                <img src="{{ route('images.serve', ['id' => $imageB->id, 'type' => $typeB]) }}"
                     alt="Viewport B"
                     id="imgB"
                     class="compare-img"
                     draggable="false">
            @else
                <span class="text-muted">No image selected for Viewport B</span>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function setupViewport(stageId, imgId, zoomInId, zoomOutId, resetId, textId) {
        const stage = document.getElementById(stageId);
        const img = document.getElementById(imgId);
        const zoomIn = document.getElementById(zoomInId);
        const zoomOut = document.getElementById(zoomOutId);
        const reset = document.getElementById(resetId);
        const text = document.getElementById(textId);

        if (!stage || !img) return null;

        let scale = 1.0;
        let panX = 0;
        let panY = 0;
        let isPanning = false;
        let startX = 0;
        let startY = 0;

        function update() {
            img.style.transform = `translate(${panX}px, ${panY}px) scale(${scale})`;
            text.innerText = Math.round(scale * 100) + '%';
        }

        zoomIn.addEventListener('click', () => { scale = Math.min(scale + 0.2, 5.0); update(); });
        zoomOut.addEventListener('click', () => { scale = Math.max(scale - 0.2, 0.2); update(); });
        reset.addEventListener('click', () => { scale = 1.0; panX = 0; panY = 0; update(); });

        stage.addEventListener('mousedown', (e) => {
            if (e.button === 0) {
                isPanning = true;
                startX = e.clientX - panX;
                startY = e.clientY - panY;
                stage.style.cursor = 'grabbing';
            }
        });

        window.addEventListener('mousemove', (e) => {
            if (!isPanning) return;
            panX = e.clientX - startX;
            panY = e.clientY - startY;
            update();
        });

        window.addEventListener('mouseup', () => {
            isPanning = false;
            stage.style.cursor = 'grab';
        });

        stage.addEventListener('wheel', (e) => {
            e.preventDefault();
            scale = (e.deltaY < 0) ? Math.min(scale + 0.1, 5.0) : Math.max(scale - 0.1, 0.2);
            update();
        }, { passive: false });

        return {
            setScale: (s) => { scale = s; panX = 0; panY = 0; update(); }
        };
    }

    const vpA = setupViewport('stageA', 'imgA', 'zoomInA', 'zoomOutA', 'resetA', 'zoomTextA');
    const vpB = setupViewport('stageB', 'imgB', 'zoomInB', 'zoomOutB', 'resetB', 'zoomTextB');

    const syncBtn = document.getElementById('syncZoomBtn');
    if (syncBtn) {
        syncBtn.addEventListener('click', () => {
            if (vpA) vpA.setScale(1.0);
            if (vpB) vpB.setScale(1.0);
        });
    }
});
</script>
@endsection

