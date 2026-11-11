<?php

namespace Tests\Feature;

use App\Models\EmbryoCase;
use App\Models\EmbryoImage;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmbryoCaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_embryologist_login_requires_all_four_credentials(): void
    {
        $specialKey = 'EMB-KEY-7892-XYZ';
        $user = User::create([
            'name' => 'Dr. Sarah Chen',
            'email' => 'sarah.chen@embryocase.lab',
            'password' => Hash::make('EmbryoPassword123!'),
            'role' => 'embryologist',
            'status' => 'active',
            'special_key_hash' => Hash::make($specialKey),
            'email_verified_at' => now(),
        ]);

        // Attempt 1: Wrong Special Key should fail
        $response = $this->post('/login', [
            'name' => 'Dr. Sarah Chen',
            'email' => 'sarah.chen@embryocase.lab',
            'password' => 'EmbryoPassword123!',
            'special_key' => 'INVALID-KEY-000',
        ]);
        $response->assertSessionHasErrors('auth_failed');

        // Attempt 2: All 4 correct credentials should succeed and redirect to OTP verification
        $response2 = $this->post('/login', [
            'name' => 'Dr. Sarah Chen',
            'email' => 'sarah.chen@embryocase.lab',
            'password' => 'EmbryoPassword123!',
            'special_key' => $specialKey,
        ]);
        $response2->assertStatus(302);
        $response2->assertRedirect(route('auth.otp.show'));
        $this->assertTrue(session()->has('pending_auth'));
    }

    public function test_admin_login_and_otp_workflow(): void
    {
        $admin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@embryocase.lab',
            'password' => Hash::make('AdminPassword123!'),
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login/admin', [
            'email' => 'admin@embryocase.lab',
            'password' => 'AdminPassword123!',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('auth.otp.show'));
        $this->assertEquals($admin->id, session('pending_auth')['user_id']);
    }

    public function test_case_creation_generates_emb_identifier_and_day_directories(): void
    {
        $embryologist = User::create([
            'name' => 'Dr. Sarah Chen',
            'email' => 'sarah.chen@embryocase.lab',
            'password' => Hash::make('EmbryoPassword123!'),
            'role' => 'embryologist',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($embryologist);

        $response = $this->post('/cases', [
            'embryo_number' => 'E-500',
            'father_name' => 'John Doe',
            'mother_name' => 'Jane Doe',
            'collection_date' => '2026-11-10',
            'collection_time' => '09:30',
            'lab_notes' => 'Test notes',
        ]);

        $year = date('Y');
        $expectedCaseId = "EMB-{$year}-0001";

        $response->assertStatus(302);
        $response->assertRedirect(route('cases.show', $expectedCaseId));

        $this->assertDatabaseHas('embryo_cases', [
            'case_id' => $expectedCaseId,
            'embryo_number' => 'E-500',
            'father_name' => 'John Doe',
        ]);
    }

    public function test_non_destructive_image_annotation_saving(): void
    {
        $embryologist = User::create([
            'name' => 'Dr. Sarah Chen',
            'email' => 'sarah.chen@embryocase.lab',
            'password' => Hash::make('EmbryoPassword123!'),
            'role' => 'embryologist',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($embryologist);

        $case = EmbryoCase::create([
            'case_id' => 'EMB-2026-9999',
            'embryo_number' => 'E-999',
            'father_name' => 'Father',
            'mother_name' => 'Mother',
            'collection_date' => '2026-11-01',
            'collection_time' => '09:00:00',
            'total_days' => 6,
            'status' => 'in_progress',
            'user_id' => $embryologist->id,
        ]);

        $fakeOriginal = UploadedFile::fake()->image('09-00-00.png', 400, 400);
        $storagePath = "private/embryos/{$case->case_id}/Day_1/09-00-00.png";
        Storage::disk('local')->putFileAs("private/embryos/{$case->case_id}/Day_1", $fakeOriginal, '09-00-00.png');

        $image = EmbryoImage::create([
            'case_id' => $case->id,
            'user_id' => $embryologist->id,
            'day_number' => 1,
            'filename' => '09-00-00.png',
            'original_name' => 'sample.png',
            'storage_path' => $storagePath,
            'capture_time' => '09:00:00',
            'file_size' => 1024,
            'mime_type' => 'image/png',
            'has_edit' => false,
        ]);

        $vectorJson = json_encode([
            'version' => '5.3.0',
            'objects' => [
                ['type' => 'circle', 'left' => 50, 'top' => 50, 'radius' => 30, 'stroke' => '#00FF66']
            ]
        ]);

        $response = $this->postJson("/images/{$image->id}/annotations", [
            'canvas_json' => $vectorJson,
            'canvas_width' => 400,
            'canvas_height' => 400,
            'flattened_image' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'version' => 1]);

        $image->refresh();
        $this->assertTrue($image->has_edit);
        $this->assertEquals('09-00-00_E.png', $image->edited_filename);

        $this->assertDatabaseHas('image_annotations', [
            'image_id' => $image->id,
            'version' => 1,
        ]);

        $this->assertDatabaseHas('annotation_versions', [
            'image_id' => $image->id,
            'version' => 1,
        ]);

        // Verify that the Case show view displays BOTH Original and Edited cards with the exact same timestamp
        $viewResponse = $this->get(route('cases.show', ['caseId' => $case->case_id, 'day' => 1]));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('ORIGINAL');
        $viewResponse->assertSee('EDITED (_E)');
        $viewResponse->assertSee('09-00-00.png');
        $viewResponse->assertSee('09-00-00_E.png');
        $viewResponse->assertSee('09:00:00');
        $viewResponse->assertSee('SAME TIME');
    }
}

