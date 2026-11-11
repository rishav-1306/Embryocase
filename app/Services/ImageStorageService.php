<?php

namespace App\Services;

use App\Models\EmbryoCase;
use App\Models\EmbryoImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ImageStorageService
{
    /**
     * Get the private storage disk base path
     */
    public function getPrivateDisk()
    {
        return Storage::disk('local');
    }

    /**
     * Ensure directory exists for a case and day
     */
    public function ensureDayDirectory(string $caseId, int $dayNumber): string
    {
        $relativeDir = "private/embryos/{$caseId}/Day_{$dayNumber}";
        if (!$this->getPrivateDisk()->exists($relativeDir)) {
            $this->getPrivateDisk()->makeDirectory($relativeDir);
        }
        return $relativeDir;
    }

    /**
     * Store an uploaded embryo image following 24-hour timestamp naming convention
     * e.g., 09-00-00.png
     */
    public function storeOriginalImage(EmbryoCase $case, UploadedFile $file, int $dayNumber, ?string $manualCaptureTime = null): array
    {
        $this->ensureDayDirectory($case->case_id, $dayNumber);

        // Determine capture time
        $captureTime = $manualCaptureTime ? trim($manualCaptureTime) : date('H:i:s');
        if (strlen($captureTime) === 5) {
            $captureTime .= ':00'; // ensure HH:mm:ss format
        }

        // Format 24-hour timestamp filename: 09-00-00.ext
        $timeParts = explode(':', $captureTime);
        $hour = sprintf("%02d", intval($timeParts[0] ?? date('H')));
        $minute = sprintf("%02d", intval($timeParts[1] ?? date('i')));
        $second = sprintf("%02d", intval($timeParts[2] ?? date('s')));
        $baseName = "{$hour}-{$minute}-{$second}";

        $extension = strtolower($file->getClientOriginalExtension());
        if (empty($extension)) {
            $extension = 'png';
        }

        $filename = "{$baseName}.{$extension}";
        $relativeDir = "private/embryos/{$case->case_id}/Day_{$dayNumber}";
        $storagePath = "{$relativeDir}/{$filename}";

        // Prevent collision if multiple images captured at exact same second
        $counter = 1;
        while ($this->getPrivateDisk()->exists($storagePath)) {
            $filename = "{$baseName}_{$counter}.{$extension}";
            $storagePath = "{$relativeDir}/{$filename}";
            $counter++;
        }

        // Store original raw image file without lossy recompression
        $this->getPrivateDisk()->putFileAs($relativeDir, $file, $filename);

        // Extract dimensions if available via getimagesize
        $realPath = $this->getPrivateDisk()->path($storagePath);
        $width = null;
        $height = null;
        if (file_exists($realPath)) {
            $info = @getimagesize($realPath);
            if ($info) {
                $width = $info[0];
                $height = $info[1];
            }
        }

        return [
            'filename' => $filename,
            'storage_path' => $storagePath,
            'original_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType() ?: 'image/' . $extension,
            'width' => $width,
            'height' => $height,
            'capture_time' => $captureTime,
            'capture_date' => $case->collection_date ? $case->collection_date->copy()->addDays($dayNumber - 1) : now(),
        ];
    }

    /**
     * Save non-destructive annotations and generate flattened preview image
     */
    public function saveAnnotation(EmbryoImage $image, string $canvasJson, ?string $flattenedBase64Image): array
    {
        $case = $image->embryoCase;
        $dayDir = "private/embryos/{$case->case_id}/Day_{$image->day_number}";
        $this->ensureDayDirectory($case->case_id, $image->day_number);

        // 1. Save vector JSON: e.g. 09-00-00.json
        $nameWithoutExt = pathinfo($image->filename, PATHINFO_FILENAME);
        $jsonFilename = "{$nameWithoutExt}.json";
        $jsonStoragePath = "{$dayDir}/{$jsonFilename}";
        $this->getPrivateDisk()->put($jsonStoragePath, $canvasJson);

        // 2. Save flattened edited image: e.g. 09-00-00_E.png
        $editedFilename = "{$nameWithoutExt}_E.png";
        $editedStoragePath = "{$dayDir}/{$editedFilename}";

        if ($flattenedBase64Image) {
            // Strip data:image/...;base64,
            $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $flattenedBase64Image);
            $decodedData = base64_decode($base64Data);
            if ($decodedData !== false) {
                $this->getPrivateDisk()->put($editedStoragePath, $decodedData);
            }
        }

        return [
            'json_path' => $jsonStoragePath,
            'edited_filename' => $editedFilename,
            'edited_storage_path' => $editedStoragePath,
        ];
    }

    /**
     * Create downloadable ZIP archive for an Embryo Case
     */
    public function exportCaseToZip(EmbryoCase $case): string
    {
        $zipFileName = "{$case->case_id}.zip";
        $tempDir = storage_path('app/temp_exports');
        if (!file_exists($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }

        $zipFilePath = "{$tempDir}/{$zipFileName}";
        if (file_exists($zipFilePath)) {
            @unlink($zipFilePath);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Cannot initialize ZIP archive at {$zipFilePath}");
        }

        // Add Case_Metadata.json
        $metadata = [
            'case_id' => $case->case_id,
            'embryo_number' => $case->embryo_number,
            'father_name' => $case->father_name,
            'mother_name' => $case->mother_name,
            'collection_date' => $case->collection_date?->format('Y-m-d'),
            'collection_time' => $case->collection_time,
            'total_days' => $case->total_days,
            'status' => $case->status,
            'embryologist' => [
                'id' => $case->user?->id,
                'name' => $case->user?->name,
                'email' => $case->user?->email,
            ],
            'lab_notes' => $case->lab_notes,
            'additional_observations' => $case->additional_observations,
            'created_at' => $case->created_at?->toIso8601String(),
            'exported_at' => now()->toIso8601String(),
            'observations' => $case->observations->map(function ($obs) {
                return [
                    'day_number' => $obs->day_number,
                    'title' => $obs->title,
                    'description' => $obs->description,
                    'author' => $obs->user?->name,
                    'created_at' => $obs->created_at?->toIso8601String(),
                ];
            })->toArray(),
        ];

        $zip->addFromString('Case_Metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));

        // Add day folders and images
        for ($d = 1; $d <= $case->total_days; $d++) {
            $zip->addEmptyDir("Day_{$d}");
        }

        $images = $case->images;
        foreach ($images as $img) {
            $dayFolder = "Day_{$img->day_number}";

            // Original file
            if ($this->getPrivateDisk()->exists($img->storage_path)) {
                $absPath = $this->getPrivateDisk()->path($img->storage_path);
                $zip->addFile($absPath, "{$dayFolder}/{$img->filename}");
            }

            // Edited file if present
            if ($img->has_edit && $img->edited_storage_path && $this->getPrivateDisk()->exists($img->edited_storage_path)) {
                $absEditedPath = $this->getPrivateDisk()->path($img->edited_storage_path);
                $zip->addFile($absEditedPath, "{$dayFolder}/{$img->edited_filename}");
            }

            // Annotation JSON if present
            if ($img->annotation_json_path && $this->getPrivateDisk()->exists($img->annotation_json_path)) {
                $absJsonPath = $this->getPrivateDisk()->path($img->annotation_json_path);
                $jsonBasename = basename($img->annotation_json_path);
                $zip->addFile($absJsonPath, "{$dayFolder}/{$jsonBasename}");
            }
        }

        $zip->close();
        return $zipFilePath;
    }
}
