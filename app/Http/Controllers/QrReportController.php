<?php

namespace App\Http\Controllers;

use App\Enums\QrStatus;
use App\Models\AuditLog;
use App\Models\QrCode;
use App\Models\QrReport;
use Illuminate\Http\Request;

class QrReportController extends Controller
{
    private const AUTO_PAUSE_THRESHOLD = 3;

    public function create(string $slug)
    {
        return view('redirect.report', ['slug' => $slug]);
    }

    public function store(Request $request, string $slug)
    {
        $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $qr = QrCode::where('slug', $slug)->first();

        // Always show success — never reveal whether a slug exists.
        if ($qr) {
            QrReport::create([
                'qr_code_id' => $qr->id,
                'reason' => $request->input('reason'),
                'reporter_ip_hash' => hash('sha256', $request->ip().config('qr.scan_salt')),
            ]);

            $pendingReports = $qr->reports()->where('status', 'pending')->count();

            if ($pendingReports >= self::AUTO_PAUSE_THRESHOLD && $qr->status === QrStatus::Active) {
                $qr->forceFill(['status' => QrStatus::Paused, 'admin_locked' => true])->save();
                AuditLog::record('qr.auto_paused_abuse', $qr, ['reports' => $pendingReports], null);
            }
        }

        return back()->with('reported', true);
    }
}
