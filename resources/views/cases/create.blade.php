@extends('layouts.app')

@section('title', 'Create New Embryo Case')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom" style="border-color: var(--border-grey) !important;">
    <div>
        <div class="text-secondary-label">CASE REGISTRATION</div>
        <h1 class="h2 mb-0">Create New Embryo Case</h1>
    </div>
    <div>
        <a href="{{ route('cases.index') }}" class="btn btn-sci-secondary">
            &larr; CANCEL
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="sci-card">
            <div class="sci-card-header">
                <span class="sci-card-title">CASE METADATA &amp; COLLECTION RECORD</span>
                <span class="mono fw-bold" style="font-size: 14px;">{{ $caseId }}</span>
            </div>
            <div class="sci-card-body p-4">
                <form method="POST" action="{{ route('cases.store') }}" autocomplete="off">
                    @csrf

                    <!-- Auto-populated Metadata Banner -->
                    <div class="p-3 mb-4" style="background-color: var(--bg-off-white); border: 1px solid var(--border-grey);">
                        <div class="row g-3" style="font-size: 12px;">
                            <div class="col-6 col-md-3">
                                <span class="text-secondary-label d-block">PROVISIONED CASE ID:</span>
                                <strong class="mono" style="font-size: 14px;">{{ $caseId }}</strong>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-secondary-label d-block">RECORDING EMBRYOLOGIST:</span>
                                <strong>{{ auth()->user()->name }}</strong>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-secondary-label d-block">EMBRYOLOGIST ID:</span>
                                <span class="mono">EMB-USR-{{ auth()->id() }}</span>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-secondary-label d-block">SYSTEM CREATION TIME:</span>
                                <span class="mono">{{ now()->format('Y-m-d H:i') }} UTC</span>
                            </div>
                        </div>
                    </div>

                    <!-- Mandatory Clinical Fields -->
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-4">
                            <label for="embryo_number" class="form-label">1. Embryo Number <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="embryo_number"
                                   id="embryo_number"
                                   class="form-control mono @error('embryo_number') is-invalid @enderror"
                                   value="{{ old('embryo_number') }}"
                                   placeholder="e.g. E-101 / EMB-01"
                                   required
                                   autofocus>
                            @error('embryo_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="father_name" class="form-label">2. Father's Full Name <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="father_name"
                                   id="father_name"
                                   class="form-control @error('father_name') is-invalid @enderror"
                                   value="{{ old('father_name') }}"
                                   placeholder="Full legal name"
                                   required>
                            @error('father_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-4">
                            <label for="mother_name" class="form-label">3. Mother's Full Name <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="mother_name"
                                   id="mother_name"
                                   class="form-control @error('mother_name') is-invalid @enderror"
                                   value="{{ old('mother_name') }}"
                                   placeholder="Full legal name"
                                   required>
                            @error('mother_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label for="collection_date" class="form-label">4. Collection Date <span class="text-danger">*</span></label>
                            <input type="date"
                                   name="collection_date"
                                   id="collection_date"
                                   class="form-control @error('collection_date') is-invalid @enderror"
                                   value="{{ old('collection_date', date('Y-m-d')) }}"
                                   required>
                            @error('collection_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="collection_time" class="form-label">5. Collection Time <span class="text-danger">*</span></label>
                            <input type="time"
                                   name="collection_time"
                                   id="collection_time"
                                   class="form-control @error('collection_time') is-invalid @enderror"
                                   value="{{ old('collection_time', date('H:i')) }}"
                                   required>
                            @error('collection_time')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Optional Clinical Notes -->
                    <div class="mb-3">
                        <label for="lab_notes" class="form-label">6. Laboratory Notes (Optional)</label>
                        <textarea name="lab_notes"
                                  id="lab_notes"
                                  rows="3"
                                  class="form-control"
                                  placeholder="Clinical incubation parameters, culture medium, incubator chamber ID, etc.">{{ old('lab_notes') }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label for="additional_observations" class="form-label">7. Additional Observations (Optional)</label>
                        <textarea name="additional_observations"
                                  id="additional_observations"
                                  rows="2"
                                  class="form-control"
                                  placeholder="Pre-insemination observations, morphology notes, or protocol exceptions...">{{ old('additional_observations') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top" style="border-color: var(--border-grey) !important;">
                        <a href="{{ route('cases.index') }}" class="btn btn-sci-secondary">CANCEL</a>
                        <button type="submit" class="btn btn-sci-primary px-4 py-2">
                            INITIALIZE CASE &amp; GENERATE DAY 1–6 REPOSITORY &rarr;
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

