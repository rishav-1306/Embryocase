<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AnnotationVersion;
use App\Models\EmbryoCase;
use App\Models\EmbryoImage;
use App\Models\ImageAnnotation;
use App\Services\ImageStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EmbryoImageController extends Controller
{
    protected ImageStorageService $storageService;

    public function __construct(ImageStorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    /**
     * Handle individual or bulk image upload for a case and day
     */
    public function upload(Request $request, $caseId)
    {
        $case = EmbryoCase::where('case_id', $caseId)->firstOrFail();
        $user = Auth::user();

        if ($user->isEmbryologist() && $case->user_id !== $user->id) {
            abort(403, 'Unauthorized access to upload images for this case.');
        }

        $request->validate([
            'day_number' => ['required', 'integer', 'min:1'],
            'images' => ['required', 'array'],
            'images.*' => ['required', 'file', 'mimes:jpg,jpeg,png,tiff,tif,webp', 'max:20480'], // max 20MB
            'capture_time' => ['nullable', 'string'],
        ], [
            'images.required' => 'Please select at least one image to upload.',
            'images.*.mimes' => 'Images must be in JPG, JPEG, PNG, TIFF or WEBP format.',
            'images.*.max' => 'Each image file cannot exceed 20MB.',
        ]);

        $dayNumber = intval($request->day_number);
        $uploadedImages = [];

        foreach ($request->file('images') as $file) {
            $data = $this->storageService->storeOriginalImage($case, $file, $dayNumber, $request->capture_time);

            $image = EmbryoImage::create([
                'case_id' => $case->id,
                'user_id' => $user->id,
                'day_number' => $dayNumber,
                'filename' => $data['filename'],
                'original_name' => $data['original_name'],
                'storage_path' => $data['storage_path'],
                'capture_time' => $data['capture_time'],
                'capture_date' => $data['capture_date'],
                'file_size' => $data['file_size'],
                'mime_type' => $data['mime_type'],
                'width' => $data['width'],
                'height' => $data['height'],
                'has_edit' => false,
            ]);

            ActivityLog::record($user->id, 'image_uploaded', $case->case_id, [
                'image_id' => $image->id,
                'filename' => $image->filename,
                'day_number' => $dayNumber,
                'size_bytes' => $image->file_size,
            ]);

            $uploadedImages[] = $image;
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => count($uploadedImages) . ' images uploaded successfully.',
                'images' => $uploadedImages,
            ]);
        }

        return redirect()->route('cases.show', ['caseId' => $case->case_id, 'day' => $dayNumber])
            ->with('status', count($uploadedImages) . ' embryo images uploaded successfully.');
    }

    /**
     * Authenticated and authorized private image stream
     */
    public function serve(Request $request, $id)
    {
        $image = EmbryoImage::with('embryoCase')->findOrFail($id);
        $user = Auth::user();

        if ($user->isEmbryologist() && $image->embryoCase->user_id !== $user->id) {
            abort(403, 'Unauthorized access to image binary.');
        }

        $type = $request->query('type', 'original'); // 'original' or 'edited'
        $path = ($type === 'edited' && $image->has_edit && $image->edited_storage_path)
            ? $image->edited_storage_path
            : $image->storage_path;

        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'Image file not found on private storage.');
        }

        $fileContent = Storage::disk('local')->get($path);
        $mimeType = ($type === 'edited') ? 'image/png' : $image->mime_type;

        return response($fileContent, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Dedicated Scientific Image Viewer
     */
    public function view($id)
    {
        $image = EmbryoImage::with(['embryoCase.user', 'annotation', 'annotationVersions.user'])
            ->findOrFail($id);

        $user = Auth::user();
        if ($user->isEmbryologist() && $image->embryoCase->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        return view('images.view', compact('image'));
    }

    /**
     * Scientific Image Annotation Editor (Fabric.js workspace)
     */
    public function editor($id)
    {
        $image = EmbryoImage::with(['embryoCase', 'annotation', 'annotationVersions'])
            ->findOrFail($id);

        $user = Auth::user();
        if ($user->isEmbryologist() && $image->embryoCase->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        return view('images.editor', compact('image'));
    }

    /**
     * Save non-destructive annotations (JSON + Flattened preview)
     */
    public function saveAnnotation(Request $request, $id)
    {
        $image = EmbryoImage::with('embryoCase')->findOrFail($id);
        $user = Auth::user();

        if ($user->isEmbryologist() && $image->embryoCase->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $request->validate([
            'canvas_json' => ['required', 'string'],
            'canvas_width' => ['required', 'integer'],
            'canvas_height' => ['required', 'integer'],
            'flattened_image' => ['nullable', 'string'], // base64 PNG data
        ]);

        // Save files
        $result = $this->storageService->saveAnnotation(
            $image,
            $request->canvas_json,
            $request->flattened_image
        );

        // Update or create image_annotations
        $annotation = ImageAnnotation::where('image_id', $image->id)->first();
        $nextVersion = $annotation ? ($annotation->version + 1) : 1;

        if ($annotation) {
            $annotation->update([
                'user_id' => $user->id,
                'canvas_data' => $request->canvas_json,
                'canvas_width' => $request->canvas_width,
                'canvas_height' => $request->canvas_height,
                'version' => $nextVersion,
            ]);
        } else {
            $annotation = ImageAnnotation::create([
                'image_id' => $image->id,
                'user_id' => $user->id,
                'canvas_data' => $request->canvas_json,
                'canvas_width' => $request->canvas_width,
                'canvas_height' => $request->canvas_height,
                'version' => 1,
            ]);
        }

        // Add version history record
        AnnotationVersion::create([
            'annotation_id' => $annotation->id,
            'image_id' => $image->id,
            'user_id' => $user->id,
            'version' => $nextVersion,
            'canvas_data' => $request->canvas_json,
            'edited_storage_path' => $result['edited_storage_path'],
            'notes' => "Saved by {$user->name}",
            'created_at' => now(),
        ]);

        // Update image record
        $image->update([
            'has_edit' => true,
            'edited_filename' => $result['edited_filename'],
            'edited_storage_path' => $result['edited_storage_path'],
            'annotation_json_path' => $result['json_path'],
        ]);

        ActivityLog::record($user->id, 'image_annotated', $image->embryoCase->case_id, [
            'image_id' => $image->id,
            'version' => $nextVersion,
            'filename' => $image->filename,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Non-destructive annotation and flattened preview saved successfully.',
            'version' => $nextVersion,
            'edited_url' => route('images.serve', ['id' => $image->id, 'type' => 'edited', 't' => time()]),
        ]);
    }

    /**
     * Delete an image
     */
    public function destroy($id)
    {
        $image = EmbryoImage::with('embryoCase')->findOrFail($id);
        $user = Auth::user();

        if ($user->isEmbryologist() && $image->embryoCase->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $caseId = $image->embryoCase->case_id;
        $dayNumber = $image->day_number;
        $filename = $image->filename;

        $image->delete();

        ActivityLog::record($user->id, 'image_deleted', $caseId, [
            'image_id' => $image->id,
            'filename' => $filename,
            'day_number' => $dayNumber,
        ]);

        return redirect()->route('cases.show', ['caseId' => $caseId, 'day' => $dayNumber])
            ->with('status', "Image {$filename} removed.");
    }
}
