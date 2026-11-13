<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\EmbryologistManagementController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\EmbryologistAuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\EmbryoCaseController;
use App\Http\Controllers\EmbryoImageController;
use App\Http\Controllers\EmbryologistDashboardController;
use App\Http\Controllers\ImageComparisonController;
use App\Http\Controllers\ObservationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — EMBRYOCASE
|--------------------------------------------------------------------------
*/

// Root Redirect
Route::get('/', function () {
    if (Auth::check()) {
        return Auth::user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('embryologist.dashboard');
    }
    return redirect()->route('login');
});

// Authentication Routes
Route::prefix('')->group(function () {
    // Embryologist Login (4 Mandatory Credentials)
    Route::get('/login', [EmbryologistAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [EmbryologistAuthController::class, 'submitCredentials'])->name('login.embryologist.submit');

    // Admin Login (Email + Password)
    Route::get('/login/admin', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login/admin', [AdminAuthController::class, 'submitCredentials'])->name('admin.login.submit');

    // OTP Verification Flow (Reused for Admin & Embryologist)
    Route::get('/login/otp-verify', [OtpController::class, 'showVerifyForm'])->name('auth.otp.show');
    Route::post('/login/otp-verify', [OtpController::class, 'verifyOtp'])->name('auth.otp.verify');
    Route::post('/login/otp-resend', [OtpController::class, 'resendOtp'])->name('auth.otp.resend');

    // Logout
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    // Forgot Password & Reset via OTP
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetOtp'])->name('password.email');
    Route::get('/password-reset/verify', [ForgotPasswordController::class, 'showVerifyForm'])->name('password.verify.show');
    Route::post('/password-reset/verify', [ForgotPasswordController::class, 'verifyResetOtp'])->name('password.verify.submit');
    Route::get('/password-reset/new', [ForgotPasswordController::class, 'showNewPasswordForm'])->name('password.reset.new.show');
    Route::post('/password-reset/new', [ForgotPasswordController::class, 'updatePassword'])->name('password.reset.new.submit');
});

// Embryologist Workspace Routes (Accessible by authorized Embryologists and Admins)
Route::middleware(['embryologist'])->group(function () {
    Route::get('/dashboard', [EmbryologistDashboardController::class, 'index'])->name('embryologist.dashboard');
    Route::get('/my-activity', [EmbryologistDashboardController::class, 'activity'])->name('embryologist.activity');

    // Embryo Cases
    Route::get('/cases', [EmbryoCaseController::class, 'index'])->name('cases.index');
    Route::get('/cases/create', [EmbryoCaseController::class, 'create'])->name('cases.create');
    Route::post('/cases', [EmbryoCaseController::class, 'store'])->name('cases.store');
    Route::get('/cases/{caseId}', [EmbryoCaseController::class, 'show'])->name('cases.show');
    Route::get('/cases/{caseId}/edit', [EmbryoCaseController::class, 'edit'])->name('cases.edit');
    Route::put('/cases/{caseId}', [EmbryoCaseController::class, 'update'])->name('cases.update');
    Route::post('/cases/{caseId}/add-day', [EmbryoCaseController::class, 'addDay'])->name('cases.add-day');
    Route::delete('/cases/{caseId}/delete-day', [EmbryoCaseController::class, 'deleteDay'])->name('cases.delete-day');
    Route::get('/cases/{caseId}/export', [EmbryoCaseController::class, 'export'])->name('cases.export');

    // Images & Uploads
    Route::post('/cases/{caseId}/images/upload', [EmbryoImageController::class, 'upload'])->name('cases.images.upload');
    Route::get('/images/{id}/serve', [EmbryoImageController::class, 'serve'])->name('images.serve');
    Route::get('/images/{id}/view', [EmbryoImageController::class, 'view'])->name('images.view');
    Route::get('/images/{id}/editor', [EmbryoImageController::class, 'editor'])->name('images.editor');
    Route::post('/images/{id}/annotations', [EmbryoImageController::class, 'saveAnnotation'])->name('images.annotations.save');
    Route::delete('/images/{id}', [EmbryoImageController::class, 'destroy'])->name('images.destroy');
    Route::delete('/images/{id}/edit', [EmbryoImageController::class, 'destroyEdit'])->name('images.destroy-edit');

    // Comparison & Observations
    Route::get('/cases/{caseId}/compare', [ImageComparisonController::class, 'compare'])->name('cases.compare');
    Route::post('/cases/{caseId}/observations', [ObservationController::class, 'store'])->name('cases.observations.store');
    Route::delete('/observations/{id}', [ObservationController::class, 'destroy'])->name('observations.destroy');
});

// Admin Control Console Routes
Route::middleware(['admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    // Case Repository (Admin View)
    Route::get('/cases', [EmbryoCaseController::class, 'index'])->name('admin.cases.index');

    // Embryologist Management
    Route::get('/embryologists', [EmbryologistManagementController::class, 'index'])->name('admin.embryologists.index');
    Route::get('/embryologists/create', [EmbryologistManagementController::class, 'create'])->name('admin.embryologists.create');
    Route::post('/embryologists', [EmbryologistManagementController::class, 'store'])->name('admin.embryologists.store');
    Route::get('/embryologists/{id}', [EmbryologistManagementController::class, 'show'])->name('admin.embryologists.show');
    Route::post('/embryologists/{id}/status', [EmbryologistManagementController::class, 'updateStatus'])->name('admin.embryologists.status');
    Route::post('/embryologists/{id}/regenerate-key', [EmbryologistManagementController::class, 'regenerateKey'])->name('admin.embryologists.regenerate-key');
    Route::post('/embryologists/{id}/revoke-key', [EmbryologistManagementController::class, 'revokeKey'])->name('admin.embryologists.revoke-key');

    // Audit History & Backups
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('admin.activity.index');
    Route::get('/backups', [BackupController::class, 'index'])->name('admin.backups.index');
    Route::post('/backups', [BackupController::class, 'createBackup'])->name('admin.backups.create');
    Route::get('/backups/{id}/download', [BackupController::class, 'download'])->name('admin.backups.download');
});
