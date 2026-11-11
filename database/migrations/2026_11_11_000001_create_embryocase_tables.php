<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Embryo Cases
        Schema::create('embryo_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_id', 32)->unique(); // e.g. EMB-2026-0001
            $table->string('embryo_number', 64)->index();
            $table->string('father_name', 128);
            $table->string('mother_name', 128);
            $table->date('collection_date')->index();
            $table->time('collection_time');
            $table->unsignedTinyInteger('total_days')->default(6);
            $table->text('lab_notes')->nullable();
            $table->text('additional_observations')->nullable();
            $table->string('status', 32)->default('in_progress')->index(); // in_progress, completed, archived
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Embryo Images
        Schema::create('embryo_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('embryo_cases')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_number')->index(); // Day 1 to 6+
            $table->string('filename', 64); // e.g. 09-00-00.png
            $table->string('original_name', 255);
            $table->string('storage_path', 500);
            $table->time('capture_time')->index();
            $table->date('capture_date')->nullable();
            $table->unsignedBigInteger('file_size'); // bytes
            $table->string('mime_type', 64);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->boolean('has_edit')->default(false)->index();
            $table->string('edited_filename', 64)->nullable(); // e.g. 09-00-00_E.png
            $table->string('edited_storage_path', 500)->nullable();
            $table->string('annotation_json_path', 500)->nullable(); // e.g. 09-00-00.json
            $table->timestamps();
            $table->softDeletes();

            $table->index(['case_id', 'day_number']);
        });

        // 3. Image Annotations (Fabric.js vector metadata)
        Schema::create('image_annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('image_id')->constrained('embryo_images')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->longText('canvas_data'); // Fabric.js JSON object
            $table->unsignedInteger('canvas_width');
            $table->unsignedInteger('canvas_height');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });

        // 4. Annotation Versions (History)
        Schema::create('annotation_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annotation_id')->constrained('image_annotations')->cascadeOnDelete();
            $table->foreignId('image_id')->constrained('embryo_images')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->longText('canvas_data');
            $table->string('edited_storage_path', 500)->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // 5. Clinical Observations
        Schema::create('observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('embryo_cases')->cascadeOnDelete();
            $table->foreignId('image_id')->nullable()->constrained('embryo_images')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_number')->nullable();
            $table->string('title', 191);
            $table->text('description');
            $table->timestamps();
        });

        // 6. OTP Verifications
        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('email', 191)->index();
            $table->string('otp_hash', 255);
            $table->string('purpose', 32); // 'login', 'password_reset'
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(5);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        // 7. Special Key History
        Schema::create('special_key_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('key_hash', 255);
            $table->string('action', 32); // 'generated', 'regenerated', 'revoked'
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // 8. Audit Activity Logs
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64)->index();
            $table->string('case_id', 32)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        // 9. Backup Logs
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('filename', 255);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('backup_type', 32); // 'database', 'images', 'full'
            $table->string('status', 32); // 'completed', 'failed'
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('special_key_history');
        Schema::dropIfExists('otp_verifications');
        Schema::dropIfExists('observations');
        Schema::dropIfExists('annotation_versions');
        Schema::dropIfExists('image_annotations');
        Schema::dropIfExists('embryo_images');
        Schema::dropIfExists('embryo_cases');
    }
};

