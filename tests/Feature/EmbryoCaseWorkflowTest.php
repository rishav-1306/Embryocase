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

        // Attempt 2: All correct credentials should succeed and redirect to OTP verification
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

    public function test_embryologist_sequential_step_by_step_authentication_flow(): void
    {
        $specialKey = 'RISHAV1306';
        $user = User::create([
            'name' => 'Aryan',
            'email' => 'nikhilaryan0223@gmail.com',
            'password' => Hash::make('Aryan@2005'),
            'role' => 'embryologist',
            'status' => 'active',
            'special_key_hash' => Hash::make($specialKey),
            'email_verified_at' => now(),
        ]);

        // Step 1: Initial load shows Step 1 Name input
        $step1Get = $this->get('/login');
        $step1Get->assertStatus(200);
        $step1Get->assertSee('Step 1: Enter Your Full Name');
        $step1Get->assertDontSee('Step 2: Enter Secret Key');

        // Step 1: Invalid Name
        $step1Fail = $this->post('/login', [
            'step' => 1,
            'name' => 'Unknown Person',
        ]);
        $step1Fail->assertSessionHasErrors('name');

        // Step 1: Valid Name
        $step1Success = $this->post('/login', [
            'step' => 1,
            'name' => 'Aryan',
        ]);
        $step1Success->assertRedirect('/login');
        $this->assertEquals(2, session('embryo_auth_step'));
        $this->assertEquals('Aryan', session('embryo_auth_name'));

        // Step 2: Renders Step 2 Secret Key input
        $step2Get = $this->get('/login');
        $step2Get->assertStatus(200);
        $step2Get->assertSee('Step 2: Enter Secret Key');
        $step2Get->assertSee('Aryan');
        $step2Get->assertDontSee('Step 1: Enter Your Full Name');

        // Step 2: Invalid Secret Key
        $step2Fail = $this->post('/login', [
            'step' => 2,
            'special_key' => 'WRONG-KEY',
        ]);
        $step2Fail->assertSessionHasErrors('special_key');

        // Step 2: Valid Secret Key
        $step2Success = $this->post('/login', [
            'step' => 2,
            'special_key' => $specialKey,
        ]);
        $step2Success->assertRedirect('/login');
        $this->assertEquals(3, session('embryo_auth_step'));
        $this->assertEquals($user->id, session('embryo_auth_user_id'));

        // Step 3: Renders Step 3 Email input
        $step3Get = $this->get('/login');
        $step3Get->assertStatus(200);
        $step3Get->assertSee('Step 3: Enter Registered Email');
        $step3Get->assertSee('KEY VERIFIED');

        // Step 3: Invalid Email
        $step3Fail = $this->post('/login', [
            'step' => 3,
            'email' => 'wrong.email@gmail.com',
        ]);
        $step3Fail->assertSessionHasErrors('email');

        // Step 3: Valid Email -> Dispatches OTP and redirects to OTP verification
        $step3Success = $this->post('/login', [
            'step' => 3,
            'email' => 'nikhilaryan0223@gmail.com',
        ]);
        $step3Success->assertStatus(302);
        $step3Success->assertRedirect(route('auth.otp.show'));
        $this->assertEquals($user->id, session('pending_auth')['user_id']);
        $this->assertEquals('nikhilaryan0223@gmail.com', session('pending_auth')['email']);

        // Check step session variables were wiped clean
        $this->assertFalse(session()->has('embryo_auth_step'));
        $this->assertFalse(session()->has('embryo_auth_name'));
    }

    public function test_embryologist_login_reset_clears_step_session(): void
    {
        $this->withSession([
            'embryo_auth_step' => 2,
            'embryo_auth_name' => 'Aryan',
        ]);

        $response = $this->get('/login/reset');
        $response->assertRedirect('/login');
        $this->assertFalse(session()->has('embryo_auth_step'));
        $this->assertFalse(session()->has('embryo_auth_name'));
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

        // Non-existent admin email fails
        $fail = $this->post('/login/admin', [
            'email' => 'unknown.admin@embryocase.lab',
        ]);
        $fail->assertSessionHasErrors('email');

        // Valid admin email succeeds and sends OTP
        $response = $this->post('/login/admin', [
            'email' => 'admin@embryocase.lab',
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
        $viewResponse->assertSee('Delete Embryo Image');
    }

    public function test_ayush_embryologist_login_workflow(): void
    {
        $ayush = User::create([
            'name' => 'ayush',
            'email' => 'rishavsingh.1105@gmail.com',
            'password' => Hash::make('Ayush@2006'),
            'role' => 'embryologist',
            'status' => 'active',
            'special_key_hash' => Hash::make('RISHAV1306'),
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'name' => 'ayush',
            'email' => 'rishavsingh.1105@gmail.com',
            'password' => 'Ayush@2006',
            'special_key' => 'RISHAV1306',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('auth.otp.show'));
        $this->assertEquals($ayush->id, session('pending_auth')['user_id']);
    }

    public function test_delete_embryo_image_removes_from_database(): void
    {
        $user = User::create([
            'name' => 'ayush',
            'email' => 'rishavsingh.1105@gmail.com',
            'password' => Hash::make('Ayush@2006'),
            'role' => 'embryologist',
            'status' => 'active',
            'special_key_hash' => Hash::make('RISHAV1306'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $case = EmbryoCase::create([
            'case_id' => 'EMB-2026-0005',
            'embryo_number' => 'E-005',
            'father_name' => 'Father',
            'mother_name' => 'Mother',
            'collection_date' => '2026-11-01',
            'collection_time' => '09:00:00',
            'total_days' => 6,
            'status' => 'in_progress',
            'user_id' => $user->id,
        ]);

        $fakeOriginal = UploadedFile::fake()->image('10-00-00.png', 300, 300);
        $storagePath = "private/embryos/{$case->case_id}/Day_1/10-00-00.png";
        Storage::disk('local')->putFileAs("private/embryos/{$case->case_id}/Day_1", $fakeOriginal, '10-00-00.png');

        $image = EmbryoImage::create([
            'case_id' => $case->id,
            'user_id' => $user->id,
            'day_number' => 1,
            'filename' => '10-00-00.png',
            'original_name' => '10-00-00.png',
            'storage_path' => $storagePath,
            'capture_time' => '10:00:00',
            'file_size' => 1024,
            'mime_type' => 'image/png',
            'has_edit' => false,
        ]);

        $this->assertDatabaseHas('embryo_images', ['id' => $image->id]);

        $deleteResponse = $this->delete(route('images.destroy', $image->id));
        $deleteResponse->assertStatus(302);

        // Soft-deleted in database, but file preserved on disk in 24h trash buffer
        $this->assertSoftDeleted('embryo_images', ['id' => $image->id]);
        $this->assertTrue(Storage::disk('local')->exists($storagePath));

        // Restore image from Trash
        $restoreResponse = $this->post(route('images.restore', $image->id));
        $restoreResponse->assertStatus(302);
        $this->assertNotSoftDeleted('embryo_images', ['id' => $image->id]);
        $this->assertTrue(Storage::disk('local')->exists($storagePath));

        // Delete again and Force Purge
        $this->delete(route('images.destroy', $image->id));
        $forceResponse = $this->delete(route('images.force-delete', $image->id));
        $forceResponse->assertStatus(302);
        $this->assertDatabaseMissing('embryo_images', ['id' => $image->id]);
        $this->assertFalse(Storage::disk('local')->exists($storagePath));
    }

    public function test_missing_today_acquisition_warning_notification(): void
    {
        $embryologist = User::create([
            'name' => 'Dr. Chen',
            'email' => 'dr.chen@embryocase.lab',
            'password' => Hash::make('Secret123!'),
            'role' => 'embryologist',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($embryologist);

        // Case collected 2 days ago -> Today is Day 3
        $case = EmbryoCase::create([
            'case_id' => 'EMB-2026-0007',
            'embryo_number' => 'E-007',
            'father_name' => 'Father',
            'mother_name' => 'Mother',
            'collection_date' => now()->subDays(2)->format('Y-m-d'),
            'collection_time' => '09:00:00',
            'total_days' => 6,
            'status' => 'in_progress',
            'user_id' => $embryologist->id,
        ]);

        $this->assertEquals(3, $case->today_development_day);
        $this->assertTrue($case->isTodayImageMissing());

        // Check Case Details view shows the warning
        $caseView = $this->get(route('cases.show', $case->case_id));
        $caseView->assertStatus(200);
        $caseView->assertSee("ACQUISITION IMAGE IS NOT UPLOADED (DAY 3)");

        // Check Dashboard view shows the warning
        $dashView = $this->get(route('embryologist.dashboard'));
        $dashView->assertStatus(200);
        $dashView->assertSee("LABORATORY SCHEDULE ALERT");
        $dashView->assertSee("E-007");
    }

    public function test_delete_day_option_removes_day_and_decrements_total_days(): void
    {
        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin.test@embryocase.lab',
            'password' => Hash::make('AdminPass123!'),
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin);

        $case = EmbryoCase::create([
            'case_id' => 'EMB-2026-0006',
            'embryo_number' => 'E-006',
            'father_name' => 'Father',
            'mother_name' => 'Mother',
            'collection_date' => '2026-11-01',
            'collection_time' => '09:00:00',
            'total_days' => 6,
            'status' => 'in_progress',
            'user_id' => $admin->id,
        ]);

        // Add an image on Day 6
        $fakeImg = UploadedFile::fake()->image('11-00-00.png', 200, 200);
        $path = "private/embryos/{$case->case_id}/Day_6/11-00-00.png";
        Storage::disk('local')->putFileAs("private/embryos/{$case->case_id}/Day_6", $fakeImg, '11-00-00.png');

        $img = EmbryoImage::create([
            'case_id' => $case->id,
            'user_id' => $admin->id,
            'day_number' => 6,
            'filename' => '11-00-00.png',
            'original_name' => '11-00-00.png',
            'storage_path' => $path,
            'capture_time' => '11:00:00',
            'file_size' => 512,
            'mime_type' => 'image/png',
            'has_edit' => false,
        ]);

        $this->assertEquals(6, $case->fresh()->total_days);

        $response = $this->delete(route('cases.delete-day', $case->case_id), [
            'day_number' => 6,
        ]);

        $response->assertStatus(302);
        $this->assertEquals(5, $case->fresh()->total_days);
        $this->assertSoftDeleted('embryo_images', ['id' => $img->id]);
    }
}

