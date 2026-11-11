<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\EmbryoCase;
use App\Models\User;
use App\Services\ImageStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmbryoCaseController extends Controller
{
    protected ImageStorageService $storageService;

    public function __construct(ImageStorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    /**
     * Case repository listing
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = EmbryoCase::with(['user', 'images']);

        // Embryologists can only access their authorized cases
        if ($user->isEmbryologist()) {
            $query->where('user_id', $user->id);
        }

        // Filters
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('case_id', 'LIKE', "%{$s}%")
                  ->orWhere('embryo_number', 'LIKE', "%{$s}%")
                  ->orWhere('father_name', 'LIKE', "%{$s}%")
                  ->orWhere('mother_name', 'LIKE', "%{$s}%");
            });
        }

        if ($request->filled('collection_date')) {
            $query->whereDate('collection_date', $request->collection_date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($user->isAdmin() && $request->filled('embryologist_id')) {
            $query->where('user_id', $request->embryologist_id);
        }

        $cases = $query->latest('id')->paginate(15)->withQueryString();
        $embryologists = $user->isAdmin() ? User::where('role', 'embryologist')->get() : collect();

        $viewName = $user->isAdmin() ? 'admin.cases.index' : 'cases.index';
        return view($viewName, compact('cases', 'embryologists'));
    }

    /**
     * Form to create a new embryo case
     */
    public function create()
    {
        $caseId = EmbryoCase::generateCaseId();
        return view('cases.create', compact('caseId'));
    }

    /**
     * Store new embryo case in database and initialize storage
     */
    public function store(Request $request)
    {
        $request->validate([
            'embryo_number' => ['required', 'string', 'max:64'],
            'father_name' => ['required', 'string', 'max:128'],
            'mother_name' => ['required', 'string', 'max:128'],
            'collection_date' => ['required', 'date'],
            'collection_time' => ['required', 'string'],
            'lab_notes' => ['nullable', 'string'],
            'additional_observations' => ['nullable', 'string'],
        ], [
            'embryo_number.required' => 'Embryo Number is required.',
            'father_name.required' => "Father's Full Name is required.",
            'mother_name.required' => "Mother's Full Name is required.",
            'collection_date.required' => 'Collection Date is required.',
            'collection_time.required' => 'Collection Time is required.',
        ]);

        $caseId = EmbryoCase::generateCaseId();

        $case = EmbryoCase::create([
            'case_id' => $caseId,
            'embryo_number' => trim($request->embryo_number),
            'father_name' => trim($request->father_name),
            'mother_name' => trim($request->mother_name),
            'collection_date' => $request->collection_date,
            'collection_time' => $request->collection_time,
            'total_days' => 6,
            'lab_notes' => $request->lab_notes,
            'additional_observations' => $request->additional_observations,
            'status' => 'in_progress',
            'user_id' => auth()->id(),
        ]);

        // Automatically initialize private directories Day 1 to Day 6
        for ($d = 1; $d <= 6; $d++) {
            $this->storageService->ensureDayDirectory($case->case_id, $d);
        }

        ActivityLog::record(auth()->id(), 'case_created', $case->case_id, [
            'embryo_number' => $case->embryo_number,
            'collection_date' => $case->collection_date?->format('Y-m-d'),
        ]);

        return redirect()->route('cases.show', $case->case_id)->with('status', "Embryo Case {$case->case_id} successfully created.");
    }

    /**
     * View detailed case record (Folder View / Timeline View)
     */
    public function show($caseId, Request $request)
    {
        $case = EmbryoCase::where('case_id', $caseId)
            ->with(['user', 'images' => function ($q) {
                $q->orderBy('capture_time', 'asc');
            }, 'observations.user'])
            ->firstOrFail();

        // Check authorization
        $user = Auth::user();
        if ($user->isEmbryologist() && $case->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this embryo case.');
        }

        $viewMode = $request->query('view', 'folder'); // 'folder' or 'timeline'
        $activeDay = intval($request->query('day', 1));

        if ($activeDay < 1 || $activeDay > $case->total_days) {
            $activeDay = 1;
        }

        $dayImages = $case->images->where('day_number', $activeDay);

        return view('cases.show', compact('case', 'viewMode', 'activeDay', 'dayImages'));
    }

    /**
     * Edit case metadata form
     */
    public function edit($caseId)
    {
        $case = EmbryoCase::where('case_id', $caseId)->firstOrFail();

        $user = Auth::user();
        if ($user->isEmbryologist() && $case->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        return view('cases.edit', compact('case'));
    }

    /**
     * Update case metadata
     */
    public function update(Request $request, $caseId)
    {
        $case = EmbryoCase::where('case_id', $caseId)->firstOrFail();

        $user = Auth::user();
        if ($user->isEmbryologist() && $case->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'embryo_number' => ['required', 'string', 'max:64'],
            'father_name' => ['required', 'string', 'max:128'],
            'mother_name' => ['required', 'string', 'max:128'],
            'collection_date' => ['required', 'date'],
            'collection_time' => ['required', 'string'],
            'status' => ['required', 'in:in_progress,completed,archived'],
            'lab_notes' => ['nullable', 'string'],
            'additional_observations' => ['nullable', 'string'],
        ]);

        $case->update([
            'embryo_number' => trim($request->embryo_number),
            'father_name' => trim($request->father_name),
            'mother_name' => trim($request->mother_name),
            'collection_date' => $request->collection_date,
            'collection_time' => $request->collection_time,
            'status' => $request->status,
            'lab_notes' => $request->lab_notes,
            'additional_observations' => $request->additional_observations,
        ]);

        ActivityLog::record(auth()->id(), 'case_metadata_modified', $case->case_id, [
            'embryo_number' => $case->embryo_number,
        ]);

        return redirect()->route('cases.show', $case->case_id)->with('status', "Case {$case->case_id} metadata updated successfully.");
    }

    /**
     * Dynamically add another day (e.g. Day 7+)
     */
    public function addDay($caseId)
    {
        $case = EmbryoCase::where('case_id', $caseId)->firstOrFail();
        $user = Auth::user();
        if ($user->isEmbryologist() && $case->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $newDay = $case->total_days + 1;
        $case->update(['total_days' => $newDay]);
        $this->storageService->ensureDayDirectory($case->case_id, $newDay);

        ActivityLog::record(auth()->id(), 'case_day_added', $case->case_id, [
            'day_number' => $newDay,
        ]);

        return redirect()->route('cases.show', ['caseId' => $case->case_id, 'day' => $newDay])
            ->with('status', "Day {$newDay} section added to {$case->case_id}.");
    }

    /**
     * Export embryo case to ZIP archive
     */
    public function export($caseId)
    {
        $case = EmbryoCase::where('case_id', $caseId)->with(['images', 'observations.user'])->firstOrFail();
        $user = Auth::user();
        if ($user->isEmbryologist() && $case->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        try {
            $zipPath = $this->storageService->exportCaseToZip($case);

            ActivityLog::record(auth()->id(), 'data_exported', $case->case_id, [
                'type' => 'single_case_zip',
            ]);

            return response()->download($zipPath, "{$case->case_id}.zip")->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }
}
