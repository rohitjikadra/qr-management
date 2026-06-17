<?php

use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\QrReportController;
use App\Http\Controllers\RazorpayWebhookController;
use App\Http\Controllers\RedirectController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('pricing', [BillingController::class, 'pricing'])->name('pricing');

Route::get('terms', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('refund-policy', [LegalController::class, 'refund'])->name('legal.refund');

Route::get('robots.txt', function () {
    $lines = [
        'User-agent: *',
        'Allow: /',
        'Disallow: /admin',
        'Disallow: /dashboard',
        'Disallow: /qr',
        'Disallow: /billing',
        'Disallow: /settings',
        'Disallow: /webhooks/',
        '',
        'Sitemap: '.url('/sitemap.xml'),
    ];

    return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain']);
})->name('robots');

Route::get('sitemap.xml', function () {
    $urls = [
        ['loc' => url('/'), 'priority' => '1.0'],
        ['loc' => route('pricing'), 'priority' => '0.9'],
        ['loc' => route('legal.terms'), 'priority' => '0.3'],
        ['loc' => route('legal.privacy'), 'priority' => '0.3'],
        ['loc' => route('legal.refund'), 'priority' => '0.3'],
        ['loc' => route('login'), 'priority' => '0.5'],
        ['loc' => route('register'), 'priority' => '0.7'],
    ];

    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

    foreach ($urls as $url) {
        $xml .= '<url>';
        $xml .= '<loc>'.e($url['loc']).'</loc>';
        $xml .= '<changefreq>weekly</changefreq>';
        $xml .= '<priority>'.$url['priority'].'</priority>';
        $xml .= '</url>';
    }

    $xml .= '</urlset>';

    return response($xml, 200, ['Content-Type' => 'application/xml']);
})->name('sitemap');

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
    Route::post('impersonation/stop', [ImpersonationController::class, 'stop'])->name('impersonation.stop');

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
              