<?php

declare(strict_types=1);

use App\Http\Controllers\Settings\AppearanceController;
use App\Http\Controllers\Settings\LocaleController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\Team\BillingController;
use App\Http\Controllers\Settings\Team\CancelController;
use App\Http\Controllers\Settings\Team\CheckoutController;
use App\Http\Controllers\Settings\Team\PortalController;
use App\Http\Controllers\Settings\Team\ResumeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::patch('settings/locale', [LocaleController::class, 'update'])->name('locale.update');

    Route::get('settings/teams/billing', [BillingController::class, 'show'])->name('teams.billing.show');
    Route::get('settings/teams/billing/portal', [PortalController::class, 'show'])->name('teams.billing.portal.show');
    Route::post('settings/teams/billing/checkout', [CheckoutController::class, 'store'])->name('teams.checkout.store');
    Route::post('settings/teams/billing/cancel', [CancelController::class, 'store'])->name('teams.billing.cancel.store');
    Route::post('settings/teams/billing/resume', [ResumeController::class, 'store'])->name('teams.billing.resume.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/appearance', [AppearanceController::class, 'edit'])->name('appearance.edit');

    Route::get('settings/language', [LocaleController::class, 'edit'])->name('locale.edit');
});
