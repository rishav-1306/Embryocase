@extends('layouts.app')

@section('title', 'Edit Case Metadata: ' . $case->case_id)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom" style="border-color: var(--border-grey) !important;">
    <div>
        <div class="text-secondary-label">METADATA AUDIT &amp; MODIFICATION</div>
        <h1 class="h2 mb-0">Edit Metadata &bull; {{ $case->case_id }}</h1>
    </div>
    <div>
        <a href="{{ route('cases.show', $case->case_id) }}" class="btn btn-sci-secondary">
            &larr; BACK TO CASE
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="sci-card">
            <div class="sci-card-header">
                <span class="sci-card-title">CASE SPECIFICATIONS</span>
                <span class="mono fw-bold">{{ $case->case_id }}</span>
            </div>
            <div class="sci-card-body p-4">
                <form method="POST" action="{{ route('cases.update', $case->case_id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-3">
                            <label class="form-label">Internal Case ID</label>
                            <input type="text" class="form-control mono" value="{{ $case->case_id }}" disabled readonly>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="embryo_number" class="form-label">Embryo Number <span class="text-danger">*</span></label>
                            <input type="text" name="embryo_number" id="embryo_number" class="form-control mono" value="{{ old('embryo_number', $case->embryo_number) }}" required>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="father_name" class="form-label">Father's Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="father_name" id="father_name" class="form-control" value="{{ old('father_name', $case->father_name) }}" required>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="mother_name" class="form-label">Mother's Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="mother_name" id="mother_name" class="form-control" value="{{ old('mother_name', $case->mother_name) }}" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label for="collection_date" class="form-label">Collection Date <span class="text-danger">*</span></label>
                            <input type="date" name="collection_date" id="collection_date" class="form-control" value="{{ old('collection_date', $case->collection_date?->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="collection_time" class="form-label">Collection Time <span class="text-danger">*</span></label>
                            <input type="time" name="collection_time" id="collection_time" class="form-control" value="{{ old('collection_time', $case->collection_time) }}" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="status" class="form-label">Case Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select">
                                <option value="in_progress" {{ $case->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="completed" {{ $case->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="archived" {{ $case->status === 'archived' ? 'selected' : '' }}>Archived</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="lab_notes" class="form-label">Laboratory Notes</label>
                        <textarea name="lab_notes" id="lab_notes" rows="3" class="form-control">{{ old('lab_notes', $case->lab_notes) }}</textarea>
                    </div>

                    <div class="mb-4">
                        <label for="additional_observations" class="form-label">Additional Observations</label>
                        <textarea name="additional_observations" id="additional_observations" rows="2" class="form-control">{{ old('additional_observations', $case->additional_observations) }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top" style="border-color: var(--border-grey) !important;">
                        <a href="{{ route('cases.show', $case->case_id) }}" class="btn btn-sci-secondary">CANCEL</a>
                        <button type="submit" class="btn btn-sci-primary px-4">SAVE MODIFICATIONS</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

