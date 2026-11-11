<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\AnnotationVersion;
use App\Models\EmbryoCase;
use App\Models\EmbryoImage;
use App\Models\ImageAnnotation;
use App\Models\Observation;
use App\Models\SpecialKeyHistory;
use App\Models\User;
use App\Services\ImageStorageService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class EmbryoCaseSampleSeeder extends Seeder
{
    public function run(): void
    {
        $storageService = app(ImageStorageService::class);

        // 1. Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@embryocase.lab'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('AdminPassword123!'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // 2. Embryologist 1: Dr. Sarah Chen
        $sarahKey = 'EMB-KEY-7892-XYZ';
        $sarah = User::firstOrCreate(
            ['email' => 'sarah.chen@embryocase.lab'],
            [
                'name' => 'Dr. Sarah Chen',
                'password' => Hash::make('EmbryoPassword123!'),
                'role' => 'embryologist',
                'status' => 'active',
                'special_key_hash' => Hash::make($sarahKey),
                'created_by' => $admin->id,
                'email_verified_at' => now(),
            ]
        );

        SpecialKeyHistory::firstOrCreate(
            ['user_id' => $sarah->id, 'action' => 'generated'],
            [
                'key_hash' => $sarah->special_key_hash,
                'created_by' => $admin->id,
                'created_at' => now(),
            ]
        );

        // 3. Embryologist 2: Dr. Arthur Miller
        $millerKey = 'EMB-KEY-4519-ABC';
        $miller = User::firstOrCreate(
            ['email' => 'arthur.miller@embryocase.lab'],
            [
                'name' => 'Dr. Arthur Miller',
                'password' => Hash::make('EmbryoPassword123!'),
                'role' => 'embryologist',
                'status' => 'active',
                'special_key_hash' => Hash::make($millerKey),
                'created_by' => $admin->id,
                'email_verified_at' => now(),
            ]
        );

        SpecialKeyHistory::firstOrCreate(
            ['user_id' => $miller->id, 'action' => 'generated'],
            [
                'key_hash' => $miller->special_key_hash,
                'created_by' => $admin->id,
                'created_at' => now(),
            ]
        );

        // 4. Sample Case 1: EMB-2026-0001
        $case1 = EmbryoCase::firstOrCreate(
            ['case_id' => 'EMB-2026-0001'],
            [
                'embryo_number' => 'E-101',
                'father_name' => 'Marcus Vance',
                'mother_name' => 'Elena Vance',
                'collection_date' => now()->subDays(5)->format('Y-m-d'),
                'collection_time' => '09:00:00',
                'total_days' => 6,
                'lab_notes' => "Standard IVF incubation protocol. Medium: G-1 PLUS / G-2 PLUS.\nChamber incubator 04, CO2 6.0%, O2 5.0%.",
                'additional_observations' => 'Normal 2PN fertilization verified at 17 hours post-insemination.',
                'status' => 'in_progress',
                'user_id' => $sarah->id,
            ]
        );

        // Generate microscopy sample images for Case 1 across Days 1 to 5
        for ($d = 1; $d <= 5; $d++) {
            $storageService->ensureDayDirectory($case1->case_id, $d);

            $times = ($d === 1) ? ['09-00-00', '13-30-00', '17-00-00'] : ['09-00-00', '14-00-00'];

            foreach ($times as $t) {
                $filename = "{$t}.png";
                $relPath = "private/embryos/{$case1->case_id}/Day_{$d}/{$filename}";
                $timeColon = str_replace('-', ':', $t);

                if (!Storage::disk('local')->exists($relPath)) {
                    // Create realistic scientific embryo microscopy graphic
                    $imgRes = imagecreatetruecolor(500, 500);
                    $bg = imagecolorallocate($imgRes, 18, 22, 25); // dark microscopy background
                    imagefill($imgRes, 0, 0, $bg);

                    // Illumination halo
                    $halo = imagecolorallocate($imgRes, 35, 42, 48);
                    imagefilledellipse($imgRes, 250, 250, 420, 420, $halo);

                    // Zona Pellucida (outer transparent protective shell)
                    $zonaColor = imagecolorallocate($imgRes, 160, 175, 185);
                    imagesetthickness($imgRes, 8);
                    imageellipse($imgRes, 250, 250, 340, 340, $zonaColor);

                    // Perivitelline space
                    $periColor = imagecolorallocate($imgRes, 55, 65, 75);
                    imageellipse($imgRes, 250, 250, 316, 316, $periColor);

                    // Cellular blastomeres based on Day
                    $cellFill = imagecolorallocate($imgRes, 115, 130, 140);
                    $cellBorder = imagecolorallocate($imgRes, 190, 205, 215);
                    imagesetthickness($imgRes, 3);

                    if ($d === 1) {
                        // Day 1: 1-cell or 2PN stage
                        imagefilledellipse($imgRes, 250, 250, 270, 270, $cellFill);
                        imageellipse($imgRes, 250, 250, 270, 270, $cellBorder);
                        // Pronuclei
                        $pnColor = imagecolorallocate($imgRes, 85, 95, 105);
                        imagefilledellipse($imgRes, 225, 250, 50, 50, $pnColor);
                        imagefilledellipse($imgRes, 275, 250, 50, 50, $pnColor);
                    } elseif ($d === 2) {
                        // Day 2: 4-cell stage
                        imagefilledellipse($imgRes, 205, 205, 140, 140, $cellFill);
                        imageellipse($imgRes, 205, 205, 140, 140, $cellBorder);
                        imagefilledellipse($imgRes, 295, 205, 140, 140, $cellFill);
                        imageellipse($imgRes, 295, 205, 140, 140, $cellBorder);
                        imagefilledellipse($imgRes, 205, 295, 140, 140, $cellFill);
                        imageellipse($imgRes, 205, 295, 140, 140, $cellBorder);
                        imagefilledellipse($imgRes, 295, 295, 140, 140, $cellFill);
                        imageellipse($imgRes, 295, 295, 140, 140, $cellBorder);
                    } elseif ($d === 3) {
                        // Day 3: 8-cell stage
                        for ($a = 0; $a < 8; $a++) {
                            $cx = 250 + intval(65 * cos($a * M_PI / 4));
                            $cy = 250 + intval(65 * sin($a * M_PI / 4));
                            imagefilledellipse($imgRes, $cx, $cy, 110, 110, $cellFill);
                            imageellipse($imgRes, $cx, $cy, 110, 110, $cellBorder);
                        }
                    } else {
                        // Day 4/5: Morula & Blastocyst cavity
                        imagefilledellipse($imgRes, 250, 250, 290, 290, $cellFill);
                        $trophectoderm = imagecolorallocate($imgRes, 170, 185, 195);
                        imageellipse($imgRes, 250, 250, 290, 290, $trophectoderm);
                        // Inner cell mass (ICM)
                        $icm = imagecolorallocate($imgRes, 190, 205, 215);
                        imagefilledellipse($imgRes, 200, 210, 90, 80, $icm);
                    }

                    // Lab metadata overlay on image bottom
                    $textColor = imagecolorallocate($imgRes, 200, 200, 200);
                    imagestring($imgRes, 2, 15, 475, "{$case1->case_id} | Day {$d} | {$timeColon} | 20x DIC", $textColor);

                    ob_start();
                    imagepng($imgRes);
                    $binaryData = ob_get_clean();
                    imagedestroy($imgRes);

                    Storage::disk('local')->put($relPath, $binaryData);
                }

                $imageModel = EmbryoImage::firstOrCreate(
                    ['case_id' => $case1->id, 'day_number' => $d, 'filename' => $filename],
                    [
                        'user_id' => $sarah->id,
                        'original_name' => "CAM_MC_{$d}_{$t}.png",
                        'storage_path' => $relPath,
                        'capture_time' => $timeColon,
                        'capture_date' => $case1->collection_date->copy()->addDays($d - 1),
                        'file_size' => Storage::disk('local')->size($relPath),
                        'mime_type' => 'image/png',
                        'width' => 500,
                        'height' => 500,
                        'has_edit' => false,
                    ]
                );

                // For Day 3: Create a sample annotated edited version
                if ($d === 3 && $t === '09-00-00' && !$imageModel->has_edit) {
                    $editedFilename = '09-00-00_E.png';
                    $editedRelPath = "private/embryos/{$case1->case_id}/Day_3/{$editedFilename}";
                    $jsonFilename = '09-00-00.json';
                    $jsonRelPath = "private/embryos/{$case1->case_id}/Day_3/{$jsonFilename}";

                    // Create flattened annotated image with clinical green measurement circle and arrow
                    $editRes = imagecreatetruecolor(500, 500);
                    $origBin = Storage::disk('local')->get($relPath);
                    $srcImg = imagecreatefromstring($origBin);
                    imagecopy($editRes, $srcImg, 0, 0, 0, 0, 500, 500);
                    imagedestroy($srcImg);

                    $green = imagecolorallocate($editRes, 0, 255, 102);
                    imagesetthickness($editRes, 3);
                    imageellipse($editRes, 250, 250, 340, 340, $green); // Zona measurement
                    imagestring($editRes, 4, 270, 75, "Zona: 14.8um", $green);

                    ob_start();
                    imagepng($editRes);
                    $editBinary = ob_get_clean();
                    imagedestroy($editRes);

                    Storage::disk('local')->put($editedRelPath, $editBinary);

                    // Fabric.js vector objects JSON
                    $fabricJson = [
                        'version' => '5.3.0',
                        'objects' => [
                            [
                                'type' => 'circle',
                                'left' => 80,
                                'top' => 80,
                                'radius' => 170,
                                'stroke' => '#00FF66',
                                'strokeWidth' => 3,
                                'fill' => 'transparent',
                                'selectable' => true,
                            ],
                            [
                                'type' => 'textbox',
                                'left' => 270,
                                'top' => 75,
                                'text' => 'Zona: 14.8um',
                                'fontSize' => 18,
                                'fill' => '#00FF66',
                                'fontFamily' => 'Inter, sans-serif',
                                'selectable' => true,
                            ]
                        ]
                    ];
                    Storage::disk('local')->put($jsonRelPath, json_encode($fabricJson, JSON_PRETTY_PRINT));

                    $annotation = ImageAnnotation::create([
                        'image_id' => $imageModel->id,
                        'user_id' => $sarah->id,
                        'canvas_data' => json_encode($fabricJson),
                        'canvas_width' => 500,
                        'canvas_height' => 500,
                        'version' => 1,
                    ]);

                    AnnotationVersion::create([
                        'annotation_id' => $annotation->id,
                        'image_id' => $imageModel->id,
                        'user_id' => $sarah->id,
                        'version' => 1,
                        'canvas_data' => json_encode($fabricJson),
                        'edited_storage_path' => $editedRelPath,
                        'notes' => 'Zona pellucida measurement and blastomere symmetry verified',
                        'created_at' => now(),
                    ]);

                    $imageModel->update([
                        'has_edit' => true,
                        'edited_filename' => $editedFilename,
                        'edited_storage_path' => $editedRelPath,
                        'annotation_json_path' => $jsonRelPath,
                    ]);
                }
            }
        }

        // Add sample clinical observations for Case 1
        Observation::firstOrCreate(
            ['case_id' => $case1->id, 'title' => 'Day 1 Cleavage Assessment'],
            [
                'user_id' => $sarah->id,
                'day_number' => 1,
                'description' => 'Normal fertilization verified. Both pronuclei visible at 17 hours post insemination with clear nucleolar precursor body alignment.',
            ]
        );

        Observation::firstOrCreate(
            ['case_id' => $case1->id, 'title' => 'Day 3 Compaction Evaluation'],
            [
                'user_id' => $sarah->id,
                'day_number' => 3,
                'description' => '8-cell stage embryo displaying uniform blastomere cleavage and negligible extracellular fragmentation (< 5%).',
            ]
        );

        // 5. Sample Case 2: EMB-2026-0002
        $case2 = EmbryoCase::firstOrCreate(
            ['case_id' => 'EMB-2026-0002'],
            [
                'embryo_number' => 'E-102',
                'father_name' => 'David Reynolds',
                'mother_name' => 'Claire Reynolds',
                'collection_date' => now()->subDays(2)->format('Y-m-d'),
                'collection_time' => '10:15:00',
                'total_days' => 6,
                'lab_notes' => 'ICSI procedure. Culture incubator 02.',
                'additional_observations' => 'Oocyte maturity: MII confirmed prior to microinjection.',
                'status' => 'in_progress',
                'user_id' => $miller->id,
            ]
        );

        for ($d = 1; $d <= 2; $d++) {
            $storageService->ensureDayDirectory($case2->case_id, $d);
            $filename = '10-15-00.png';
            $relPath = "private/embryos/{$case2->case_id}/Day_{$d}/{$filename}";

            if (!Storage::disk('local')->exists($relPath)) {
                $imgRes = imagecreatetruecolor(500, 500);
                $bg = imagecolorallocate($imgRes, 20, 24, 28);
                imagefill($imgRes, 0, 0, $bg);
                $halo = imagecolorallocate($imgRes, 38, 45, 52);
                imagefilledellipse($imgRes, 250, 250, 420, 420, $halo);
                $zonaColor = imagecolorallocate($imgRes, 160, 175, 185);
                imagesetthickness($imgRes, 8);
                imageellipse($imgRes, 250, 250, 340, 340, $zonaColor);
                $cellFill = imagecolorallocate($imgRes, 115, 130, 140);
                imagefilledellipse($imgRes, 250, 250, 270, 270, $cellFill);

                ob_start();
                imagepng($imgRes);
                $binaryData = ob_get_clean();
                imagedestroy($imgRes);

                Storage::disk('local')->put($relPath, $binaryData);
            }

            EmbryoImage::firstOrCreate(
                ['case_id' => $case2->id, 'day_number' => $d, 'filename' => $filename],
                [
                    'user_id' => $miller->id,
                    'original_name' => "CAM_REYNOLDS_D{$d}.png",
                    'storage_path' => $relPath,
                    'capture_time' => '10:15:00',
                    'capture_date' => $case2->collection_date->copy()->addDays($d - 1),
                    'file_size' => Storage::disk('local')->size($relPath),
                    'mime_type' => 'image/png',
                    'width' => 500,
                    'height' => 500,
                    'has_edit' => false,
                ]
            );
        }

        ActivityLog::record($admin->id, 'sample_dataset_seeded', null, [
            'cases' => ['EMB-2026-0001', 'EMB-2026-0002'],
        ]);
    }
}
