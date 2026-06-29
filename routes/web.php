<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\Portal\ContractController;
use App\Http\Controllers\Portal\ContractInterestController;
use App\Http\Controllers\Portal\DocumentDownloadController;
use App\Http\Controllers\Portal\DocumentsController;
use App\Http\Controllers\Portal\HomeController;
use App\Http\Controllers\Portal\InvestmentController;
use App\Http\Controllers\Portal\InvestmentDocumentController;
use App\Http\Controllers\Portal\KycController;
use App\Http\Controllers\Portal\KycDocumentController;
use App\Http\Controllers\Portal\NewsController;
use App\Http\Controllers\Portal\NotificationController;
use App\Http\Controllers\Portal\OnboardingController;
use App\Http\Controllers\Portal\PayoutController;
use App\Http\Controllers\Portal\PortalDashboardController;
use App\Http\Controllers\Portal\PortfolioController;
use App\Http\Controllers\Portal\SettingsController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Deep health check (DB / cache / redis / queue) for load balancers & monitors.
Route::get('/health', HealthController::class)->name('health');

// ----- Public (Phase 4.3) -----
Route::get('/', HomeController::class)->name('home');
Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');
Route::get('/contracts/{contract}', [ContractController::class, 'show'])->name('contracts.show');

// Breeze sends authenticated users here after login → forward to the portal.
Route::get('/dashboard', fn () => redirect()->route('portal.dashboard'))
    ->middleware('auth')
    ->name('dashboard');

// ----- Investor portal -----
Route::middleware(['auth', 'verified', 'investor'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', PortalDashboardController::class)->name('dashboard');

    // Onboarding wizard (Phase 5 — Feature 1).
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding');
    Route::post('/onboarding/profile', [OnboardingController::class, 'storeProfile'])->name('onboarding.profile');
    Route::post('/onboarding/documents', [OnboardingController::class, 'storeDocuments'])->name('onboarding.documents');
    Route::post('/onboarding/terms', [OnboardingController::class, 'storeTerms'])->name('onboarding.terms');
    Route::get('/portfolio', [PortfolioController::class, 'index'])->name('portfolio');
    Route::get('/investments', [InvestmentController::class, 'index'])->name('investments');
    Route::get('/investments/{investment}', [InvestmentController::class, 'show'])->name('investments.show');

    // Generated PDF documents (owner-only via InvestmentPolicy).
    Route::get('/investments/{investment}/contract', [InvestmentDocumentController::class, 'contract'])->name('investments.contract');
    Route::get('/investments/{investment}/statement', [InvestmentDocumentController::class, 'statement'])->name('investments.statement');
    Route::get('/investments/{investment}/certificate', [InvestmentDocumentController::class, 'certificate'])->name('investments.certificate');

    Route::get('/payouts', [PayoutController::class, 'index'])->name('payouts');
    Route::get('/payouts/{payout}/receipt', [InvestmentDocumentController::class, 'receipt'])->name('payouts.receipt');

    // KYC document download (owner-only, signed URL).
    Route::get('/kyc/document/{type}', [KycDocumentController::class, 'own'])
        ->middleware('signed')
        ->name('kyc.document');

    // KYC resubmission (rejected investors re-upload documents).
    Route::get('/kyc/resubmit', [KycController::class, 'resubmitForm'])->name('kyc.resubmit');
    Route::post('/kyc/resubmit', [KycController::class, 'resubmit'])->name('kyc.resubmit.store');
    Route::get('/contracts', [ContractController::class, 'index'])->name('contracts');
    Route::post('/contracts/{contract}/interest', [ContractInterestController::class, 'store'])->name('contracts.interest');
    Route::get('/documents', [DocumentsController::class, 'index'])->name('documents');
    Route::get('/news', [NewsController::class, 'index'])->name('news');
    Route::get('/profile', [App\Http\Controllers\Portal\ProfileController::class, 'index'])->name('profile');

    // Account settings & security center.
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::patch('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::post('/settings/logout-others', [SettingsController::class, 'logoutOtherSessions'])->name('settings.logoutOthers');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
});

// Admin KYC document download (admin-only via policy, signed URL).
Route::get('/kyc/admin/{user}/{type}', [KycDocumentController::class, 'admin'])
    ->middleware(['auth', 'signed'])
    ->name('kyc.admin.document');

// Document center download (owner or admin via policy, signed URL).
Route::get('/documents/{document}/download', DocumentDownloadController::class)
    ->middleware(['auth', 'signed'])
    ->name('documents.download');

// ----- Breeze account settings (unchanged) -----
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
