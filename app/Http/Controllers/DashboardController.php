<?php

namespace App\Http\Controllers;

use App\Models\QrCode;
use App\Models\QrScanEvent;
use App\Services\PlanLimitService;
use App\Services\QrContentBuilder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PlanLimitService $planLimits, QrContentBuilder $contentBuilder)
    {
        $user = $request->user();
        $plan = $planLimits->planFor($user);

        $qrStats = $user->qrCodes()
            ->selectRaw("COUNT(*) as total, COUNT(*) FILTER (WHERE status = 'active') as active, COALESCE(SUM(scan_count), 0) as total_scans")
            ->toBase()
            ->first();

        $scansThisMonth = QrScanEvent::query()
            ->whereIn('qr_code_id', $user->qrCodes()->select('id'))
            ->where('scanned_at', '>=', now()->startOfMonth())
            ->count();

        $recentQrs = $user->qrCodes()->latest()->limit(5)->get()->map(fn (QrCode $qr) => [
            'id' => $qr->id,
            'name' => $qr->name,
            'type_label' => $qr->type->label(),
            'is_dynamic' => $qr->is_dynamic,
            'status' => $qr->status->value,
            'scan_count' => $qr->scan_count,
            'payload' => $contentBuilder->payloadFor($qr),
        ]);

        return Inertia::render('dashboard', [
            'stats' => [
                'total_qr' => (int) $qrStats->total,
                'active_qr' => (int) $qrStats->active,
                'total_scans' => (int) $qrStats->total_scans,
                'scans_this_month' => $scansThisMonth,
            ],
            'plan' => [
                'name' => $plan->name,
                'is_free' => $plan->isFree(),
                'scans_per_month' => (int) $plan->limit('scans_per_month'),
            ],
            'recentQrs' => $recentQrs,
        ]);
    }
}
