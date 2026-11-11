<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\EmbryoCase;
use App\Models\Observation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ObservationController extends Controller
{
    public function store(Request $request, $caseId)
    {
        $case = EmbryoCase::where('case_id', $caseId)->firstOrFail();
        $user = Auth::user();

        if ($user->isEmbryologist() && $case->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'description' => ['required', 'string'],
            'day_number' => ['nullable', 'integer', 'min:1'],
            'image_id' => ['nullable', 'exists:embryo_images,id'],
        ]);

        $observation = Observation::create([
            'case_id' => $case->id,
            'image_id' => $request->image_id,
            'user_id' => $user->id,
            'day_number' => $request->day_number,
            'title' => trim($request->title),
            'description' => trim($request->description),
        ]);

        ActivityLog::record($user->id, 'observation_added', $case->case_id, [
            'observation_id' => $observation->id,
            'title' => $observation->title,
        ]);

        return back()->with('status', 'Clinical observation recorded successfully.');
    }

    public function destroy($id)
    {
        $observation = Observation::with('embryoCase')->findOrFail($id);
        $user = Auth::user();

        if ($user->isEmbryologist() && $observation->embryoCase->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $caseId = $observation->embryoCase->case_id;
        $observation->delete();

        ActivityLog::record($user->id, 'observation_deleted', $caseId);

        return back()->with('status', 'Clinical observation removed.');
    }
}
