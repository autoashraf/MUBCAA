<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\MemberDashboardController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\VerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::redirect('/home', '/')->name('home.redirect');
Route::get('/r/{user}', [AuthController::class, 'affiliateRedirect'])->name('affiliate.redirect');
Route::match(['get', 'post'], '/language/{locale}', function (Request $request, string $locale) {
    abort_unless(in_array($locale, config('app.supported_locales', ['en', 'bn']), true), 404);

    $request->session()->put('locale', $locale);

    if ($request->expectsJson()) {
        return response()->json([
            'ok' => true,
            'locale' => $locale,
        ]);
    }

    return redirect()->back();
})->name('locale.switch');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:member-login')->name('login.attempt');
    Route::post('/login/check', [AuthController::class, 'checkLoginIdentifier'])->middleware('throttle:login-check')->name('login.check');
    Route::post('/login/verify', [AuthController::class, 'verifyLoginOtp'])->middleware('throttle:login-otp-verify')->name('login.verify');
    Route::post('/login/resend', [AuthController::class, 'resendLoginOtp'])->middleware('throttle:login-otp-resend')->name('login.resend');
    Route::get('/admin/login', [AuthController::class, 'showAdminLogin'])->name('admin.login');
    Route::post('/admin/login', [AuthController::class, 'adminLogin'])->middleware('throttle:admin-login')->name('admin.login.attempt');
    Route::get('/membership/apply-now', [AuthController::class, 'showRegistration'])->name('membership.apply');
    Route::post('/membership/apply-now/check', [AuthController::class, 'checkRegistrationField'])->middleware('throttle:registration-check')->name('membership.apply.check');
    Route::post('/membership/apply-now', [AuthController::class, 'register'])->middleware('throttle:registration-submit')->name('membership.apply.store');
});

Route::get('/membership/verify-contacts', [VerificationController::class, 'show'])->name('member.verification.show');
Route::post('/membership/verify-contacts/email', [VerificationController::class, 'verifyEmail'])->middleware('throttle:contact-verification')->name('member.verification.email');
Route::post('/membership/verify-contacts/mobile', [VerificationController::class, 'verifyMobile'])->middleware('throttle:contact-verification')->name('member.verification.mobile');
Route::post('/membership/verify-contacts/{channel}/resend', [VerificationController::class, 'resend'])->middleware('throttle:contact-verification-resend')->name('member.verification.resend');

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
    Route::get('/', [SiteController::class, 'page'])->defaults('key', 'memories-list')->name('memories.list');
});

Route::get('/contact', [SiteController::class, 'page'])->defaults('key', 'contact')->name('contact');
Route::post('/contact', [SiteController::class, 'storeContact'])->name('contact.store');
Route::get('/privacy-policy', [SiteController::class, 'page'])->defaults('key', 'privacy-policy')->name('privacy.policy');
Route::get('/terms-and-conditions', [SiteController::class, 'page'])->defaults('key', 'terms-conditions')->name('terms.conditions');
Route::get('/cookie-policy', [SiteController::class, 'page'])->defaults('key', 'cookie-policy')->name('cookie.policy');
Route::get('/disclaimer', [SiteController::class, 'page'])->defaults('key', 'disclaimer')->name('disclaimer');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [MemberDashboardController::class, 'index'])->name('member.dashboard');
    Route::get('/profile', [MemberDashboardController::class, 'profile'])->name('member.profile');
    Route::get('/membership/profile-completion/{step?}', [MemberDashboardController::class, 'showCompletion'])->name('member.profile.complete');
    Route::post('/membership/profile-completion', [MemberDashboardController::class, 'saveCompletion'])->name('member.profile.complete.save');
    Route::put('/dashboard/profile', [MemberDashboardController::class, 'updateProfile'])->name('member.profile.update');
    Route::get('/documents/profile', [DocumentController::class, 'profile'])->name('member.documents.profile');
    Route::get('/documents/id-card', [DocumentController::class, 'idCard'])->name('member.documents.id-card');
    Route::get('/documents/certificate', [DocumentController::class, 'certificate'])->name('member.documents.certificate');
    Route::get('/memories/submit-your-memory', [SiteController::class, 'submitMemory'])->name('memories.submit');
    Route::post('/memories/submit-your-memory', [SiteController::class, 'storeMemory'])->name('memories.store');
});

Route::prefix('admin')->middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/applications', [AdminController::class, 'applications'])->name('admin.applications.index');
    Route::get('/memories', [AdminController::class, 'memories'])->name('admin.memories.index');
    Route::get('/gallery', [AdminController::class, 'gallery'])->name('admin.gallery.index');
    Route::get('/videos', [AdminController::class, 'videos'])->name('admin.videos.index');
    Route::get('/contacts', [AdminController::class, 'contacts'])->name('admin.contacts.index');
    Route::get('/affiliates', [AdminController::class, 'affiliates'])->name('admin.affiliates.index');
    Route::get('/applications/{application}', [AdminController::class, 'show'])->name('admin.applications.show');
    Route::post('/applications/{application}/edit', [AdminController::class, 'update'])->name('admin.applications.update');
    Route::post('/applications/{application}/approve', [AdminController::class, 'approve'])->name('admin.applications.approve');
    Route::post('/applications/{application}/reject', [AdminController::class, 'reject'])->name('admin.applications.reject');
    Route::post('/memories/{memorySubmission}/approve', [AdminController::class, 'approveMemory'])->name('admin.memories.approve');
    Route::post('/memories/{memorySubmission}/reject', [AdminController::class, 'rejectMemory'])->name('admin.memories.reject');
    Route::post('/gallery', [AdminController::class, 'storeGalleryPhoto'])->name('admin.gallery.store');
    Route::post('/gallery/{galleryPhoto}/delete', [AdminController::class, 'destroyGalleryPhoto'])->name('admin.gallery.destroy');
    Route::post('/videos', [AdminController::class, 'storeGalleryVideo'])->name('admin.videos.store');
    Route::post('/videos/{galleryVideo}/delete', [AdminController::class, 'destroyGalleryVideo'])->name('admin.videos.destroy');
});
