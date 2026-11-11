@extends('layouts.app')

@section('title', 'Annotation Editor: ' . $image->filename)

@section('styles')
<style>
    .editor-wrapper {
        border: 1px solid var(--border-grey);
        background-color: var(--bg-white);
        display: flex;
        flex-direction: column;
        height: calc(100vh - 140px);
        min-height: 600px;
    }
    .editor-top-bar {
        background-color: var(--bg-off-white);
        border-bottom: 1px solid var(--border-grey);
        padding: 0.5rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .editor-body {
        display: flex;
        flex: 1;
        overflow: hidden;
    }
    .editor-left-bar {
        width: 64px;
        background-color: var(--bg-white);
        border-right: 1px solid var(--border-grey);
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0.5rem 0;
        gap: 0.35rem;
    }
    .tool-btn {
        width: 48px;
        height: 48px;
        border: 1px solid transparent;
        background-color: transparent;
        color: var(--text-black);
        font-size: 11px;
        font-weight: 700;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        padding: 2px;
        transition: all 0.15s ease;
        border-radius: 0;
    }
    .tool-btn:hover {
        background-color: var(--bg-light-grey);
        border-color: var(--border-grey);
    }
    .tool-btn.active {
        background-color: var(--text-black);
        color: var(--bg-white);
        border-color: var(--text-black);
    }
    .tool-btn span {
        font-size: 9px;
        letter-spacing: -0.5px;
        margin-top: 2px;
        text-transform: uppercase;
    }
    .editor-stage-area {
        flex: 1;
        background-color: #1a1a1a;
        overflow: auto;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    .color-swatch {
        width: 24px;
        height: 24px;
        border: 1px solid #E5E5E5;
        cursor: pointer;
        display: inline-block;
    }
    .color-swatch.active {
        outline: 2px solid #000000;
    }
</style>
@endsection

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-2 pb-2 border-bottom" style="border-color: var(--border-grey) !important;">
    <div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-secondary-label">SCIENTIFIC ANNOTATION EDITOR</span>
            <span class="mono fw-bold">{{ $image->embryoCase->case_id }}</span>
            <span class="badge-sci badge-sci-neutral">DAY {{ $image->day_number }}</span>
            <span class="mono">{{ $image->filename }}</span>
            @if($image->has_edit && $image->annotation)
                <span class="badge-sci badge-sci-active">VERSION {{ $image->annotation->version }}</span>
            @else
                <span class="badge-sci badge-sci-neutral">VERSION 1 (NEW)</span>
            @endif
        </div>
    </div>
    <div class="mt-2 mt-md-0 d-flex gap-2">
        <a href="{{ route('images.view', $image->id) }}" class="btn btn-sm btn-sci-secondary">
            &larr; CANCEL / EXIT
        </a>
        <button type="button" class="btn btn-sm btn-sci-primary px-3" id="saveAnnotationBtn">
            SAVE ANNOTATION &rarr;
        </button>
    </div>
</div>

<div class="editor-wrapper">
    <!-- TOP TOOLBAR -->
    <div class="editor-top-bar">
        <!-- Undo / Redo / History -->
        <div class="d-flex align-items-center gap-1 border-end pe-3" style="border-color: var(--border-grey) !important;">
            <button type="button" class="btn btn-sm btn-sci-subtle" id="undoBtn" title="Undo (Ctrl+Z)">UNDO</button>
            <button type="button" class="btn btn-sm btn-sci-subtle" id="redoBtn" title="Redo (Ctrl+Y)">REDO</button>
            <button type="button" class="btn btn-sm btn-sci-subtle" id="deleteSelectedBtn" title="Delete Selected Object">DELETE</button>
            <button type="button" class="btn btn-sm btn-sci-subtle" id="clearAllBtn" title="Clear All Annotations">CLEAR ALL</button>
        </div>

        <!-- Annotation Color Selection (Allowed in scientific clinical editor) -->
        <div class="d-flex align-items-center gap-2 border-end pe-3" style="border-color: var(--border-grey) !important;">
            <span class="text-secondary-label" style="font-size: 11px;">COLOR:</span>
            <input type="color" id="colorPicker" value="#00FF66" title="Pick Custom Clinical Marking Color" style="width: 32px; height: 30px; border: 1px solid var(--border-grey); cursor: pointer; padding: 0;">
            <div class="d-flex gap-1">
                <span class="color-swatch active" style="background-color: #00FF66;" data-color="#00FF66" title="Clinical Green"></span>
                <span class="color-swatch" style="background-color: #FF0033;" data-color="#FF0033" title="Alert Red"></span>
                <span class="color-swatch" style="background-color: #FFCC00;" data-color="#FFCC00" title="Measurement Yellow"></span>
                <span class="color-swatch" style="background-color: #00CCFF;" data-color="#00CCFF" title="Cyan"></span>
                <span class="color-swatch" style="background-color: #FFFFFF;" data-color="#FFFFFF" title="High Contrast White"></span>
                <span class="color-swatch" style="background-color: #000000;" data-color="#000000" title="Black"></span>
            </div>
        </div>

        <!-- Stroke / Brush Width -->
        <div class="d-flex align-items-center gap-2 border-end pe-3" style="border-color: var(--border-grey) !important;">
            <span class="text-secondary-label" style="font-size: 11px;">STROKE:</span>
            <input type="range" id="brushWidth" min="1" max="25" value="3" style="width: 80px;">
            <span class="mono" id="brushWidthVal" style="font-size: 12px; width: 24px;">3px</span>
        </div>

        <!-- Text Size -->
        <div class="d-flex align-items-center gap-2 border-end pe-3" style="border-color: var(--border-grey) !important;">
            <span class="text-secondary-label" style="font-size: 11px;">TEXT:</span>
            <select id="textSizeSelect" class="form-select form-select-sm" style="width: 80px; font-size: 11px;">
                <option value="16">16px</option>
                <option value="20" selected>20px</option>
                <option value="24">24px</option>
                <option value="32">32px</option>
                <option value="48">48px</option>
            </select>
        </div>

        <!-- Canvas Zoom Controls -->
        <div class="d-flex align-items-center gap-1">
            <button type="button" class="btn btn-sm btn-sci-subtle" id="canvasZoomOutBtn">-</button>
            <span class="mono" id="canvasZoomText" style="font-size: 11px; width: 45px; text-align: center;">100%</span>
            <button type="button" class="btn btn-sm btn-sci-subtle" id="canvasZoomInBtn">+</button>
            <button type="button" class="btn btn-sm btn-sci-subtle" id="canvasZoomResetBtn">RESET</button>
        </div>
    </div>

    <!-- MAIN BODY -->
    <div class="editor-body">
        <!-- LEFT TOOLBAR (9 TOOLS) -->
        <div class="editor-left-bar">
            <!-- 1. Selection Tool -->
            <button type="button" class="tool-btn active" id="toolSelect" title="1. Selection & Move Tool (V)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3l7 18 3-7 7-3L3 3z"/></svg>
                <span>SELECT</span>
            </button>

            <!-- 2. Freehand Pen -->
            <button type="button" class="tool-btn" id="toolPen" title="2. Freehand Pen (P)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/></svg>
                <span>PEN</span>
            </button>

            <!-- 3. Brush -->
            <button type="button" class="tool-btn" id="toolBrush" title="3. Highlighter Brush (B)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2l4 4-10 10H8v-4L18 2z"/><path d="M4 22h16"/></svg>
                <span>BRUSH</span>
            </button>

            <!-- 4. Straight Line -->
            <button type="button" class="tool-btn" id="toolLine" title="4. Straight Line (L)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="19" x2="19" y2="5"/></svg>
                <span>LINE</span>
            </button>

            <!-- 5. Arrow -->
            <button type="button" class="tool-btn" id="toolArrow" title="5. Directional Arrow (A)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="19" x2="19" y2="5"/><polyline points="10 5 19 5 19 14"/></svg>
                <span>ARROW</span>
            </button>

            <!-- 6. Circle -->
            <button type="button" class="tool-btn" id="toolCircle" title="6. Circle / Ellipse (C)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/></svg>
                <span>CIRCLE</span>
            </button>

            <!-- 7. Rectangle -->
            <button type="button" class="tool-btn" id="toolRect" title="7. Rectangle (R)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="0"/></svg>
                <span>RECT</span>
            </button>

            <!-- 8. Text Box -->
            <button type="button" class="tool-btn" id="toolText" title="8. Text Box (T) - Editable after placement">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>
                <span>TEXT</span>
            </button>

            <!-- 9. Eraser / Delete -->
            <button type="button" class="tool-btn" id="toolEraser" title="9. Eraser (Click object to remove)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 20H7L3 16C2 15 2 13 3 12L13 2L22 11L20 20Z"/></svg>
                <span>ERASER</span>
            </button>
        </div>

        <!-- CANVAS STAGE -->
        <div class="editor-stage-area" id="stageContainer">
            <div id="canvasInnerWrapper">
                <canvas id="fabricCanvas"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('vendor/fabric/fabric.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const originalImageUrl = "{{ route('images.serve', ['id' => $image->id, 'type' => 'original']) }}";
    const saveUrl = "{{ route('images.annotations.save', $image->id) }}";
    const existingJson = {!! json_encode($image->annotation?->canvas_data ? json_decode($image->annotation->canvas_data) : null) !!};

    const canvasElement = document.getElementById('fabricCanvas');
    const stageContainer = document.getElementById('stageContainer');
    const colorPicker = document.getElementById('colorPicker');
    const brushWidthInput = document.getElementById('brushWidth');
    const brushWidthVal = document.getElementById('brushWidthVal');
    const textSizeSelect = document.getElementById('textSizeSelect');
    const swatches = document.querySelectorAll('.color-swatch');

    const toolSelect = document.getElementById('toolSelect');
    const toolPen = document.getElementById('toolPen');
    const toolBrush = document.getElementById('toolBrush');
    const toolLine = document.getElementById('toolLine');
    const toolArrow = document.getElementById('toolArrow');
    const toolCircle = document.getElementById('toolCircle');
    const toolRect = document.getElementById('toolRect');
    const toolText = document.getElementById('toolText');
    const toolEraser = document.getElementById('toolEraser');

    const undoBtn = document.getElementById('undoBtn');
    const redoBtn = document.getElementById('redoBtn');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const clearAllBtn = document.getElementById('clearAllBtn');
    const saveBtn = document.getElementById('saveAnnotationBtn');

    let currentTool = 'select';
    let currentColor = '#00FF66';
    let currentStrokeWidth = 3;
    let currentFontSize = 20;

    let canvasScale = 1.0;
    const canvasZoomText = document.getElementById('canvasZoomText');
    const canvasZoomInBtn = document.getElementById('canvasZoomInBtn');
    const canvasZoomOutBtn = document.getElementById('canvasZoomOutBtn');
    const canvasZoomResetBtn = document.getElementById('canvasZoomResetBtn');

    // History stacks for Undo / Redo
    let historyStack = [];
    let redoStack = [];
    let isHistoryLocked = false;

    // Initialize Fabric Canvas
    const canvas = new fabric.Canvas('fabricCanvas', {
        selection: true,
        preserveObjectStacking: true,
    });

    // Load Background Image
    fabric.Image.fromURL(originalImageUrl, function(img) {
        // Set canvas dimensions equal to original image dimensions for 1:1 scientific accuracy
        const origWidth = img.width;
        const origHeight = img.height;

        canvas.setWidth(origWidth);
        canvas.setHeight(origHeight);

        // Make background image fixed, unselectable and non-interactive
        img.set({
            originX: 'left',
            originY: 'top',
            selectable: false,
            evented: false,
        });

        canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas));

        // Restore previous annotations if available
        if (existingJson && existingJson.objects) {
            canvas.loadFromDatalessJSON(existingJson, function() {
                canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas));
                saveState();
            });
        } else {
            saveState();
        }

        fitToContainer();
    }, { crossOrigin: 'anonymous' });

    function fitToContainer() {
        const containerWidth = stageContainer.clientWidth - 40;
        const containerHeight = stageContainer.clientHeight - 40;
        const cWidth = canvas.getWidth();
        const cHeight = canvas.getHeight();

        if (cWidth > 0 && cHeight > 0) {
            const fitScale = Math.min(containerWidth / cWidth, containerHeight / cHeight, 1.0);
            setZoom(fitScale);
        }
    }

    function setZoom(scale) {
        canvasScale = Math.max(0.1, Math.min(scale, 4.0));
        canvas.setZoom(canvasScale);
        canvas.setDimensions({
            width: canvas.getWidth() * (canvasScale / canvas.getZoom()),
            height: canvas.getHeight() * (canvasScale / canvas.getZoom()),
        }, { backstoreOnly: false });
        canvasZoomText.innerText = Math.round(canvasScale * 100) + '%';
        canvas.renderAll();
    }

    canvasZoomInBtn.addEventListener('click', () => setZoom(canvasScale + 0.15));
    canvasZoomOutBtn.addEventListener('click', () => setZoom(canvasScale - 0.15));
    canvasZoomResetBtn.addEventListener('click', () => setZoom(1.0));

    // Color Swatches
    swatches.forEach(swatch => {
        swatch.addEventListener('click', function() {
            swatches.forEach(s => s.classList.remove('active'));
            this.classList.add('active');
            currentColor = this.getAttribute('data-color');
            colorPicker.value = currentColor;
            updateActiveObjectColor();
        });
    });

    colorPicker.addEventListener('input', function() {
        currentColor = this.value;
        swatches.forEach(s => s.classList.remove('active'));
        updateActiveObjectColor();
    });

    brushWidthInput.addEventListener('input', function() {
        currentStrokeWidth = parseInt(this.value);
        brushWidthVal.innerText = currentStrokeWidth + 'px';
        if (canvas.freeDrawingBrush) {
            canvas.freeDrawingBrush.width = currentStrokeWidth;
        }
        const activeObj = canvas.getActiveObject();
        if (activeObj && activeObj.stroke) {
            activeObj.set('strokeWidth', currentStrokeWidth);
            canvas.renderAll();
            saveState();
        }
    });

    textSizeSelect.addEventListener('change', function() {
        currentFontSize = parseInt(this.value);
        const activeObj = canvas.getActiveObject();
        if (activeObj && activeObj.type === 'textbox') {
            activeObj.set('fontSize', currentFontSize);
            canvas.renderAll();
            saveState();
        }
    });

    function updateActiveObjectColor() {
        if (canvas.freeDrawingBrush) {
            canvas.freeDrawingBrush.color = currentColor;
        }
        const activeObj = canvas.getActiveObject();
        if (activeObj) {
            if (activeObj.type === 'textbox') {
                activeObj.set('fill', currentColor);
            } else {
                activeObj.set('stroke', currentColor);
            }
            canvas.renderAll();
            saveState();
        }
    }

    // Tools Switching
    const toolButtons = [
        { el: toolSelect, name: 'select' },
        { el: toolPen, name: 'pen' },
        { el: toolBrush, name: 'brush' },
        { el: toolLine, name: 'line' },
        { el: toolArrow, name: 'arrow' },
        { el: toolCircle, name: 'circle' },
        { el: toolRect, name: 'rect' },
        { el: toolText, name: 'text' },
        { el: toolEraser, name: 'eraser' },
    ];

    function setTool(name) {
        currentTool = name;
        toolButtons.forEach(b => {
            if (b.name === name) b.el.classList.add('active');
            else b.el.classList.remove('active');
        });

        canvas.isDrawingMode = false;
        canvas.selection = (name === 'select');
        canvas.forEachObject(obj => {
            obj.selectable = (name === 'select' || name === 'eraser');
        });

        if (name === 'pen') {
            canvas.isDrawingMode = true;
            canvas.freeDrawingBrush = new fabric.PencilBrush(canvas);
            canvas.freeDrawingBrush.color = currentColor;
            canvas.freeDrawingBrush.width = currentStrokeWidth;
        } else if (name === 'brush') {
            canvas.isDrawingMode = true;
            canvas.freeDrawingBrush = new fabric.PencilBrush(canvas);
            // Semi-transparent clinical highlighter
            canvas.freeDrawingBrush.color = currentColor;
            canvas.freeDrawingBrush.width = currentStrokeWidth * 3;
        }

        canvas.renderAll();
    }

    toolButtons.forEach(b => {
        b.el.addEventListener('click', () => setTool(b.name));
    });

    // Drawing shapes interaction
    let isDrawingShape = false;
    let shapeOriginX = 0;
    let shapeOriginY = 0;
    let activeShape = null;

    canvas.on('mouse:down', function(o) {
        const pointer = canvas.getPointer(o.e);

        if (currentTool === 'eraser') {
            if (o.target) {
                canvas.remove(o.target);
                canvas.renderAll();
                saveState();
            }
            return;
        }

        if (currentTool === 'text') {
            const textbox = new fabric.IText('Clinical Note', {
                left: pointer.x,
                top: pointer.y,
                fontSize: currentFontSize,
                fill: currentColor,
                fontFamily: 'Inter, sans-serif',
                editable: true,
            });
            canvas.add(textbox);
            canvas.setActiveObject(textbox);
            textbox.enterEditing();
            textbox.selectAll();
            setTool('select');
            saveState();
            return;
        }

        if (['line', 'arrow', 'circle', 'rect'].includes(currentTool)) {
            isDrawingShape = true;
            shapeOriginX = pointer.x;
            shapeOriginY = pointer.y;

            if (currentTool === 'line') {
                activeShape = new fabric.Line([pointer.x, pointer.y, pointer.x, pointer.y], {
                    stroke: currentColor,
                    strokeWidth: currentStrokeWidth,
                    selectable: false,
                });
            } else if (currentTool === 'arrow') {
                activeShape = new fabric.Line([pointer.x, pointer.y, pointer.x, pointer.y], {
                    stroke: currentColor,
                    strokeWidth: currentStrokeWidth,
                    selectable: false,
                });
            } else if (currentTool === 'circle') {
                activeShape = new fabric.Circle({
                    left: pointer.x,
                    top: pointer.y,
                    radius: 1,
                    stroke: currentColor,
                    strokeWidth: currentStrokeWidth,
                    fill: 'transparent',
                    selectable: false,
                });
            } else if (currentTool === 'rect') {
                activeShape = new fabric.Rect({
                    left: pointer.x,
                    top: pointer.y,
                    width: 1,
                    height: 1,
                    stroke: currentColor,
                    strokeWidth: currentStrokeWidth,
                    fill: 'transparent',
                    selectable: false,
                });
            }

            if (activeShape) {
                canvas.add(activeShape);
            }
        }
    });

    canvas.on('mouse:move', function(o) {
        if (!isDrawingShape || !activeShape) return;
        const pointer = canvas.getPointer(o.e);

        if (currentTool === 'line' || currentTool === 'arrow') {
            activeShape.set({ x2: pointer.x, y2: pointer.y });
        } else if (currentTool === 'circle') {
            const radius = Math.sqrt(Math.pow(pointer.x - shapeOriginX, 2) + Math.pow(pointer.y - shapeOriginY, 2)) / 2;
            activeShape.set({
                radius: Math.max(1, radius),
                left: Math.min(shapeOriginX, pointer.x),
                top: Math.min(shapeOriginY, pointer.y),
            });
        } else if (currentTool === 'rect') {
            activeShape.set({
                left: Math.min(shapeOriginX, pointer.x),
                top: Math.min(shapeOriginY, pointer.y),
                width: Math.abs(pointer.x - shapeOriginX),
                height: Math.abs(pointer.y - shapeOriginY),
            });
        }

        canvas.renderAll();
    });

    canvas.on('mouse:up', function(o) {
        if (isDrawingShape && activeShape) {
            // If Arrow, add arrowhead
            if (currentTool === 'arrow') {
                const pointer = canvas.getPointer(o.e);
                const x1 = shapeOriginX;
                const y1 = shapeOriginY;
                const x2 = pointer.x;
                const y2 = pointer.y;

                // Calculate angle for arrow head
                const angle = Math.atan2(y2 - y1, x2 - x1);
                const headLen = Math.max(12, currentStrokeWidth * 4);

                const arrowHead = new fabric.Triangle({
                    left: x2,
                    top: y2,
                    originX: 'center',
                    originY: 'center',
                    pointType: 'arrow_head',
                    angle: (angle * 180 / Math.PI) + 90,
                    width: headLen,
                    height: headLen,
                    fill: currentColor,
                    selectable: false,
                });

                // Group the line and arrowhead together
                canvas.remove(activeShape);
                const arrowLine = new fabric.Line([x1, y1, x2, y2], {
                    stroke: currentColor,
                    strokeWidth: currentStrokeWidth,
                });

                const arrowGroup = new fabric.Group([arrowLine, arrowHead], {
                    selectable: true,
                });
                canvas.add(arrowGroup);
            } else {
                activeShape.set('selectable', true);
            }

            isDrawingShape = false;
            activeShape = null;
            setTool('select');
            saveState();
        }
    });

    // History Save / Undo / Redo
    function saveState() {
        if (isHistoryLocked) return;
        const json = JSON.stringify(canvas.toJSON(['selectable']));
        historyStack.push(json);
        redoStack = [];
    }

    canvas.on('object:modified', saveState);
    canvas.on('path:created', saveState);

    undoBtn.addEventListener('click', function() {
        if (historyStack.length > 1) {
            isHistoryLocked = true;
            const current = historyStack.pop();
            redoStack.push(current);
            const prev = historyStack[historyStack.length - 1];

            const bg = canvas.backgroundImage;
            canvas.loadFromJSON(prev, function() {
                canvas.setBackgroundImage(bg, canvas.renderAll.bind(canvas));
                isHistoryLocked = false;
            });
        }
    });

    redoBtn.addEventListener('click', function() {
        if (redoStack.length > 0) {
            isHistoryLocked = true;
            const next = redoStack.pop();
            historyStack.push(next);

            const bg = canvas.backgroundImage;
            canvas.loadFromJSON(next, function() {
                canvas.setBackgroundImage(bg, canvas.renderAll.bind(canvas));
                isHistoryLocked = false;
            });
        }
    });

    deleteSelectedBtn.addEventListener('click', function() {
        const active = canvas.getActiveObjects();
        if (active.length > 0) {
            active.forEach(obj => canvas.remove(obj));
            canvas.discardActiveObject();
            canvas.renderAll();
            saveState();
        }
    });

    clearAllBtn.addEventListener('click', function() {
        if (confirm("Clear all annotations on this image? The original microscopy image will remain completely intact.")) {
            canvas.getObjects().forEach(obj => canvas.remove(obj));
            canvas.renderAll();
            saveState();
        }
    });

    // Keyboard Shortcuts
    window.addEventListener('keydown', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

        if ((e.ctrlKey || e.metaKey) && e.key === 'z') {
            e.preventDefault();
            undoBtn.click();
        } else if ((e.ctrlKey || e.metaKey) && e.key === 'y') {
            e.preventDefault();
            redoBtn.click();
        } else if (e.key === 'Delete' || e.key === 'Backspace') {
            const active = canvas.getActiveObject();
            if (active && !active.isEditing) {
                e.preventDefault();
                deleteSelectedBtn.click();
            }
        } else if (e.key === 'v') setTool('select');
        else if (e.key === 'p') setTool('pen');
        else if (e.key === 'l') setTool('line');
        else if (e.key === 'c') setTool('circle');
        else if (e.key === 'r') setTool('rect');
        else if (e.key === 't') setTool('text');
    });

    // Save Annotations to Backend
    saveBtn.addEventListener('click', function() {
        saveBtn.disabled = true;
        saveBtn.innerText = 'SAVING & FLATTENING...';

        // 1. Vector JSON representing full Fabric.js state
        const vectorJson = JSON.stringify(canvas.toJSON());

        // 2. Export full resolution flattened PNG preview
        // Temporarily reset canvas zoom to 1.0 to export at full original image resolution
        const origZoom = canvas.getZoom();
        canvas.setZoom(1.0);
        canvas.setDimensions({
            width: canvas.getWidth() / origZoom,
            height: canvas.getHeight() / origZoom,
        });

        const flattenedDataUrl = canvas.toDataURL({
            format: 'png',
            quality: 1.0,
            multiplier: 1.0,
        });

        // Restore viewer zoom
        canvas.setZoom(origZoom);
        canvas.setDimensions({
            width: canvas.getWidth() * origZoom,
            height: canvas.getHeight() * origZoom,
        });

        const payload = {
            _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            canvas_json: vectorJson,
            canvas_width: Math.round(canvas.getWidth() / origZoom),
            canvas_height: Math.round(canvas.getHeight() / origZoom),
            flattened_image: flattenedDataUrl,
        };

        fetch(saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': payload._token,
            },
            body: JSON.stringify(payload),
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok: ' + response.statusText);
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert("Annotation version " + data.version + " saved successfully. Redirecting to Image Viewer.");
                window.location.href = "{{ route('images.view', $image->id) }}";
            } else {
                alert("Error saving: " + data.message);
                saveBtn.disabled = false;
                saveBtn.innerText = 'SAVE ANNOTATION →';
            }
        })
        .catch(err => {
            alert("Save failed: " + err.message);
            saveBtn.disabled = false;
            saveBtn.innerText = 'SAVE ANNOTATION →';
        });
    });
});
</script>
@endsection
