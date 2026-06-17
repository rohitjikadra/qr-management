<?php

use App\Http\Controllers\Admin\PaymentControlsController;
use App\Http\Controllers\Admin\AdminTeamController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\BlockedDomainController;
use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\QrCodeController;
use App\Http\Controllers\Admin\QrReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::redirect('/', '/admin/dashboard');
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/export/csv', [UserController::class, 'export'])->name('users.export');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('users/{user}/ban', [UserController::class, 'ban'])->name('users.ban');
        Route::post('users/{user}/unban', [UserController::class, 'unban'])->name('users.unban');
        Route::post('users/{user}/discount', [UserController::class, 'setDiscount'])->name('users.discount');
        Route::post('users/{user}/complimentary', [UserController::class, 'grantComplimentary'])->name('users.complimentary');
        Route::post('users/{user}/impersonate', [UserController::class, 'impersonate'])->name('users.impersonate');
        Route::post('users/{user}/resend-verification', [UserController::class, 'resendVerification'])->name('users.resend-verification');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('team', [AdminTeamController::class, 'index'])
            ->middleware('super_admin')
            ->name('team.index');
        Route::get('team/create', [AdminTeamController::class, 'create'])
            ->middleware('super_admin')
            ->name('team.create');
        Route::post('team', [AdminTeamController::class, 'store'])
            ->middleware('super_admin')
            ->name('team.store');

        Route::get('qr-codes', [QrCodeController::class, 'index'])->name('qr-codes.index');
        Route::get('qr-codes/{qrCode}', [QrCodeController::class, 'show'])->name('qr-codes.show');
        Route::post('qr-codes/{qrCode}/pause', [QrCodeController::class, 'pause'])->name('qr-codes.pause');

        Route::get('qr-reports', [QrReportController::class, 'index'])->name('qr-reports.index');
        Route::post('qr-reports/{qrReport}/dismiss', [QrReportController::class, 'dismiss'])->name('qr-reports.dismiss');
        Route::post('qr-reports/{qrReport}/pause-qr', [QrReportController::class, 'pauseQr'])->name('qr-reports.pause-qr');
        Route::post('qr-reports/{qrReport}/ban-user', [QrReportController::class, 'banUser'])->name('qr-reports.ban-user');

        Route::get('blocked-domains', [BlockedDomainController::class, 'index'])->name('blocked-domains.index');
        Route::get('blocked-domains/create', [BlockedDomainController::class, 'create'])->name('blocked-domains.create');
        Route::post('blocked-domains', [BlockedDomainController::class, 'store'])->name('blocked-domains.store');
        Route::get('blocked-domains/{blockedDomain}/edit', [BlockedDomainController::class, 'edit'])->name('blocked-domains.edit');
        Route::put('blocked-domains/{blockedDomain}', [BlockedDomainController::class, 'update'])->name('blocked-domains.update');
        Route::delete('blocked-domains/{blockedDomain}', [BlockedDomainController::class, 'destroy'])->name('blocked-domains.destroy');

        Route::get('plans', [PlanController::class, 'index'])->name('plans.index');
        Route::get('plans/{plan}/edit', [PlanController::class, 'edit'])->name('plans.edit');
        Route::put('plans/{plan}', [PlanController::class, 'update'])->name('plans.update');

        Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('subscriptions/{subscription}', [SubscriptionController::class, 'show'])->name('subscriptions.show');
        Route::post('subscriptions/{subscription}/extend', [SubscriptionController::class, 'extend'])->name('subscriptions.extend');
        Route::post('subscriptions/{subscription}/revoke', [SubscriptionController::class, 'revoke'])->name('subscriptions.revoke');

        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/export/csv', [PaymentController::class, 'export'])->name('payments.export');
        Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
        Route::post('payments/{payment}/refund', [PaymentController::class, 'markRefunded'])->name('payments.refund');

        Route::get('billing/payment-controls', [PaymentControlsController::class, 'edit'])
            ->middleware('super_admin')
            ->name('billing.payment-controls');
        Route::put('billing/payment-controls', [PaymentControlsController::class, 'update'])
            ->middleware('super_admin')
            ->name('billing.payment-controls.update');

        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        Route::get('settings/branding', [BrandingController::class, 'edit'])->name('settings.branding');
        Route::post('settings/branding', [BrandingController::class, 'update'])->name('settings.branding.update');

        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');
    });
