<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\QrReportController;
use App\Http\Controllers\RazorpayWebhookController;
use App\Http\Controllers\RedirectController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::get('pricing', [BillingController::class, 'pricing'])->name('pricing');

Route::post('webhooks/razorpay', RazorpayWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.razorpay');

// QR redirect hot path — keep this lean.
Route::get('q/{slug}', RedirectController::class)
    ->middleware('throttle:60,1')
    ->name('qr.redirect');

Route::get('q/{slug}/report', [QrReportController::class, 'create'])
    ->middleware('throttle:10,1')
    ->name('qr.report.create');
Route::post('q/{slug}/report', [QrReportController::class, 'store'])
    ->middleware('throttle:3,1440')
    ->name('qr.report.store');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('qr', QrCodeController::class)
        ->parameters(['qr' => 'qr_code'])
        ->names('qr');
    Route::post('qr/{qr_code}/toggle-status', [QrCodeController::class, 'toggleStatus'])->name('qr.toggle-status');
    Route::get('qr/{qr_code}/download', [QrCodeController::class, 'download'])->name('qr.download');

    Route::get('billing', [BillingController::class, 'index'])->name('billing');
    Route::post('billing/subscribe', [BillingController::class, 'subscribe'])->name('billing.subscribe');
    Route::post('billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
