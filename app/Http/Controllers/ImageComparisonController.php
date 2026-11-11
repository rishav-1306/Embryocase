<?php

namespace App\Http\Controllers;

use App\Models\EmbryoCase;
use App\Models\EmbryoImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImageComparisonController extends Controller
{
    public function compare($caseId, Request $request)
    {
        $case = EmbryoCase::where('case_id', $caseId)
            ->with(['images' => function ($q) {
                $q->orderBy('day_number', 'asc')->orderBy('capture_time', 'asc');
            }])
            ->firstOrFail();

        $user = Auth::user();
        if ($user->isEmbryologist() && $case->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $allImages = $case->images;

        $imageAId = $request->query('image_a', $allImages->first()?->id);
        $imageBId = $request->query('image_b');

        $imageA = $allImages->firstWhere('id', $imageAId);

        if (!$imageBId && $imageA) {
            // Default: if Image A has an edit, compare Original vs Edited!
            // Else choose second image in the case if available
            $imageB = $allImages->where('id', '!=', $imageA->id)->first() ?? $imageA;
            $imageBId = $imageB?->id;
        } else {
            $imageB = $allImages->firstWhere('id', $imageBId);
        }

        $typeA = $request->query('type_a', 'original');
        $typeB = $request->query('type_b', ($imageA && $imageA->has_edit && $imageAId == $imageBId) ? 'edited' : 'original');

        return view('images.compare', compact('case', 'allImages', 'imageA', 'imageB', 'typeA', 'typeB'));
    }
}
