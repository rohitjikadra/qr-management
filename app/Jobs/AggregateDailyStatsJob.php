<?php

namespace App\Jobs;

use App\Models\QrDailyStat;
use App\Models\QrScanEvent;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class AggregateDailyStatsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?CarbonImmutable $date = null,
    ) {}

    public function handle(): void
    {
        $date = $this->date ?? CarbonImmutable::yesterday();
        $start = $date->startOfDay();
        $end = $date->endOfDay();

        $totals = QrScanEvent::query()
            ->whereBetween('scanned_at', [$start, $end])
            ->groupBy('qr_code_id')
            ->select('qr_code_id', DB::raw('COUNT(*) as scans'))
            ->pluck('scans', 'qr_code_id');

        foreach ($totals as $qrCodeId => $scans) {
            QrDailyStat::updateOrCreate(
                ['qr_code_id' => $qrCodeId, 'date' => $date->toDateString()],
                [
                    'scans' => $scans,
                    'top_country' => $this->topValue($qrCodeId, 'country', $start, $end),
                    'top_device' => $this->topValue($qrCodeId, 'device_type', $start, $end),
                ]
            );
        }
    }

    private function topValue(int $qrCodeId, string $column, CarbonImmutable $start, CarbonImmutable $end): ?string
    {
        return QrScanEvent::query()
            ->where('qr_code_id', $qrCodeId)
            ->whereBetween('scanned_at', [$start, $end])
            ->whereNotNull($column)
            ->groupBy($column)
            ->orderByRaw('COUNT(*) DESC')
            ->limit(1)
            ->value($column);
    }
}
