<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QrStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\QrReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class QrReportController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->value() ?: 'pending';

        $query = QrReport::query()
            ->with(['qrCode.user:id,name,email'])
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search = trim($request->string('search')->value())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->whereLike('reason', "%{$search}%", caseSensitive: false)
                    ->orWhereHas('qrCode', fn ($qrQuery) => $qrQuery
                        ->whereLike('name', "%{$search}%", caseSensitive: false)
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->whereLike('email', "%{$search}%", caseSensitive: false)));
            });
        }

        $reports = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (QrReport $report): array => [
                'id' => $report->id,
                'reason' => $report->reason,
                'status' => $report->status,
                'created_at' => $report->created_at?->toDateTimeString(),
                'qr_code' => $report->qrCode ? [
                    'id' => $report->qrCode->id,
                    'name' => $report->qrCode->name,
                    'status' => $report->qrCode->status->value,
                    'admin_locked' => $report->qrCode->admin_locked,
                    'owner' => $report->qrCode->user ? [
                        'id' => $report->qrCode->user->id,
                        'name' => $report->qrCode->user->name,
                        'email' => $report->qrCode->user->email,
                    ] : null,
                ] : null,
                'can_ban_owner' => $report->status === 'pending'
                    && $report->qrCode?->user
                    && Gate::forUser($request->user())->allows('ban', $report->qrCode->user),
            ]);

        return Inertia::render('admin/qr-reports/index', [
            'reports' => $reports,
            'filters' => [
                'search' => $request->string('search')->value() ?: null,
                'status' => $status,
            ],
            'pendingCount' => QrReport::where('status', 'pending')->count(),
        ]);
    }

    public function dismiss(Request $request, QrReport $qrReport): RedirectResponse
    {
        abort_if($qrReport->status !== 'pending', 403);

        $qrReport->update(['status' => 'reviewed']);

        AuditLog::record('qr_report.dismissed', $qrReport, [
            'qr_code_id' => $qrReport->qr_code_id,
        ], $request->user()?->id);

        return back()->with('success', 'Report dismissed.');
    }

    public function pauseQr(Request $request, QrReport $qrReport): RedirectResponse
    {
        abort_if($qrReport->status !== 'pending', 403);
        abort_if($qrReport->qrCode === null, 404);

        $qr = $qrReport->qrCode;

        $qr->update([
            'status' => QrStatus::Paused,
            'admin_locked' => true,
        ]);

        $qrReport->update(['status' => 'actioned']);

        AuditLog::record('qr.admin_paused', $qr, [
            'paused_by' => $request->user()?->id,
            'from_report_id' => $qrReport->id,
        ], $request->user()?->id);

        AuditLog::record('qr_report.actioned', $qrReport, [
            'action' => 'pause_qr',
            'qr_code_id' => $qr->id,
        ], $request->user()?->id);

        return back()->with('success', 'QR code paused and report marked as actioned.');
    }

    public function banUser(Request $request, QrReport $qrReport): RedirectResponse
    {
        abort_if($qrReport->status !== 'pending', 403);

        $owner = $qrReport->qrCode?->user;
        abort_if($owner === null, 404);

        Gate::forUser($request->user())->authorize('ban', $owner);

        if ($owner->status !== UserStatus::Banned) {
            $owner->update(['status' => UserStatus::Banned]);

            AuditLog::record('user.banned', $owner, [
                'banned_by' => $request->user()?->id,
                'from_report_id' => $qrReport->id,
            ], $request->user()?->id);
        }

        $qrReport->update(['status' => 'actioned']);

        AuditLog::record('qr_report.actioned', $qrReport, [
            'action' => 'ban_user',
            'user_id' => $owner->id,
        ], $request->user()?->id);

        return back()->with('success', 'User banned and report marked as actioned.');
    }
}
