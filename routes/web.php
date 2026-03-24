<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\MemberDashboardController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/__mubcaa_check', function () {
    Log::info('MUBCAA debug route hit.', [
        'path' => request()->path(),
        'full_url' => request()->fullUrl(),
        'host' => request()->getHost(),
        'time' => now()->toDateTimeString(),
    ]);

    return response()->json([
        'app' => 'MUBCAA',
        'status' => 'local-route-ok',
        'time' => now()->toDateTimeString(),
        'routes_file_mtime' => date('Y-m-d H:i:s', filemtime(base_path('routes/web.php'))),
    ]);
});

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::redirect('/home', '/')->name('home.redirect');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/membership/apply-now', [AuthController::class, 'showRegistration'])->name('membership.apply');
    Route::post('/membership/apply-now', [AuthController::class, 'register'])->name('membership.apply.store');
});

Route::get('/membership/verify-contacts', [VerificationController::class, 'show'])->name('member.verification.show');
Route::post('/membership/verify-contacts/email', [VerificationController::class, 'verifyEmail'])->name('member.verification.email');
Route::post('/membership/verify-contacts/mobile', [VerificationController::class, 'verifyMobile'])->name('member.verification.mobile');
Route::post('/membership/verify-contacts/{channel}/resend', [VerificationController::class, 'resend'])->name('member.verification.resend');

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::prefix('about-us')->group(function (): void {
    Route::get('/mission-vision', [SiteController::class, 'page'])->defaults('key', 'about-mission')->name('about.mission');
    Route::get('/ad-hoc-committee', [SiteController::class, 'page'])->defaults('key', 'about-adhoc')->name('about.adhoc');
    Route::get('/sub-committee', [SiteController::class, 'page'])->defaults('key', 'about-subcommittee')->name('about.subcommittee');
});

Route::prefix('membership')->group(function (): void {
    Route::get('/why-become-a-member', [SiteController::class, 'page'])->defaults('key', 'membership-why')->name('membership.why');
    Route::get('/membership-privilege', [SiteController::class, 'page'])->defaults('key', 'membership-privilege')->name('membership.privilege');
    Route::get('/members', [SiteController::class, 'page'])->defaults('key', 'membership-members')->name('membership.members');
});

Route::prefix('events')->group(function (): void {
    Route::get('/upcoming-event', [SiteController::class, 'page'])->defaults('key', 'events-upcoming')->name('events.upcoming');
    Route::get('/photo-gallery', [SiteController::class, 'page'])->defaults('key', 'events-photos')->name('events.photos');
    Route::get('/video-gallery', [SiteController::class, 'page'])->defaults('key', 'events-videos')->name('events.videos');
});

Route::prefix('memories')->group(function (): void {
    Route::get('/submit-your-memory', [SiteController::class, 'submitMemory'])->name('memories.submit');
    Route::post('/submit-your-memory', [SiteController::class, 'storeMemory'])->name('memories.store');
    Route::get('/', [SiteController::class, 'page'])->defaults('key', 'memories-list')->name('memories.list');
});

Route::get('/contact', [SiteController::class, 'page'])->defaults('key', 'contact')->name('contact');
Route::post('/contact', [SiteController::class, 'storeContact'])->name('contact.store');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [MemberDashboardController::class, 'index'])->name('member.dashboard');
    Route::get('/membership/profile-completion/{step?}', [MemberDashboardController::class, 'showCompletion'])->name('member.profile.complete');
    Route::post('/membership/profile-completion', [MemberDashboardController::class, 'saveCompletion'])->name('member.profile.complete.save');
    Route::put('/dashboard/profile', [MemberDashboardController::class, 'updateProfile'])->name('member.profile.update');
    Route::get('/documents/profile', [DocumentController::class, 'profile'])->name('member.documents.profile');
    Route::get('/documents/id-card', [DocumentController::class, 'idCard'])->name('member.documents.id-card');
    Route::get('/documents/certificate', [DocumentController::class, 'certificate'])->name('member.documents.certificate');
});

Route::prefix('admin')->middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::post('/applications/{application}/advance', [AdminController::class, 'advance'])->name('admin.applications.advance');
    Route::post('/applications/{application}/reject', [AdminController::class, 'reject'])->name('admin.applications.reject');
});
