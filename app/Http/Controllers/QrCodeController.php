<?php

namespace App\Http\Controllers;

use App\Enums\QrStatus;
use App\Enums\QrType;
use App\Http\Requests\StoreQrCodeRequest;
use App\Http\Requests\UpdateQrCodeRequest;
use App\Models\AuditLog;
use App\Models\QrCode;
use App\Services\PlanLimitService;
use App\Services\QrContentBuilder;
use App\Services\QrImageService;
use App\Services\SlugGenerator;
use App\Services\UrlSafetyService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class QrCodeController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly QrContentBuilder $contentBuilder,
        private readonly QrImageService $imageService,
        private readonly PlanLimitService $planLimits,
    ) {}

    public function index(Request $request)
    {
        $query = $request->user()->qrCodes()->latest();

        if ($search = $request->string('search')->trim()->value()) {
            $query->whereLike('name', "%{$search}%", caseSensitive: false);
        }

        if ($type = $request->string('type')->value()) {
            $query->where('type', $type);
        }

        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        if ($request->filled('dynamic')) {
            $query->where('is_dynamic', $request->boolean('dynamic'));
        }

        $qrCodes = $query->paginate(20)->withQueryString()->through(fn (QrCode $qr) => [
            'id' => $qr->id,
            'name' => $qr->name,
            'type' => $qr->type->value,
            'type_label' => $qr->type->label(),
            'is_dynamic' => $qr->is_dynamic,
            'status' => $qr->status->value,
            'admin_locked' => $qr->admin_locked,
            'frozen' => $qr->frozen,
            'scan_count' => $qr->scan_count,
            'payload' => $this->contentBuilder->payloadFor($qr),
            'created_at' => $qr->created_at->toDateString(),
        ]);

        return Inertia::render('qr/index', [
            'qrCodes' => $qrCodes,
            'filters' => $request->only(['search', 'type', 'status', 'dynamic']),
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();

        return Inertia::render('qr/create', [
            'limits' => [
                'can_create_dynamic' => $this->planLimits->canCreateDynamicQr($user),
                'dynamic_count' => $this->planLimits->dynamicQrCount($user),
                'dynamic_limit' => $this->planLimits->dynamicQrLimit($user),
                'email_verified' => $user->hasVerifiedEmail(),
            ],
        ]);
    }

    public function store(StoreQrCodeRequest $request, SlugGenerator $slugGenerator, UrlSafetyService $urlSafety)
    {
        $user = $request->user();
        $type = QrType::from($request->input('type'));
        $content = $request->validated('content');
        $isDynamic = $request->boolean('is_dynamic');

        if (in_array($type, [QrType::Url, QrType::Whatsapp], true)) {
            $checkUrl = $type === QrType::Url
                ? $content['url']
                : $this->contentBuilder->destinationUrl($type, $content);

            if ($reason = $urlSafety->check($checkUrl)) {
                return back()->withErrors(['content.url' => $reason])->withInput();
            }
        }

        $qr = $user->qrCodes()->create([
            'name' => $request->validated('name'),
            'type' => $type,
            'content' => $content,
            'is_dynamic' => $isDynamic,
            'slug' => $isDynamic ? $slugGenerator->generate() : null,
            'destination_url' => $isDynamic ? $this->contentBuilder->destinationUrl($type, $content) : null,
            'status' => QrStatus::Active,
        ]);

        AuditLog::record('qr.created', $qr);

        return redirect()->route('qr.show', $qr)->with('success', 'QR code created.');
    }

    public function show(Request $request, QrCode $qrCode)
    {
        $this->authorize('view', $qrCode);

        return Inertia::render('qr/show', [
            'qr' => $this->presentQr($qrCode),
            'analytics' => $qrCode->is_dynamic
                ? $this->analytics($request, $qrCode)
                : null,
        ]);
    }

    private function analytics(Request $request, QrCode $qr): array
    {
        $historyDays = $this->planLimits->analyticsHistoryDays($qr->user);
        $requestedRange = in_array((int) $request->query('range'), [7, 30, 90], true)
            ? (int) $request->query('range')
            : 7;

        $range = $historyDays === PlanLimitService::UNLIMITED
            ? $requestedRange
            : min($requestedRange, $historyDays);

        $start = now()->subDays($range - 1)->startOfDay();

        $counts = $qr->scanEvents()
            ->where('scanned_at', '>=', $start)
            ->selectRaw('DATE(scanned_at) as day, COUNT(*) as scans')
            ->groupBy('day')
            ->pluck('scans', 'day');

        $series = collect(range(0, $range - 1))->map(function (int $offset) use ($start, $counts) {
            $date = $start->copy()->addDays($offset)->toDateString();

            return ['date' => $date, 'scans' => (int) ($counts[$date] ?? 0)];
        })->values();

        return [
            'range' => $range,
            'requested_range' => $requestedRange,
            'history_limit_days' => $historyDays,
            'series' => $series,
            'scans_today' => (int) ($counts[now()->toDateString()] ?? 0),
            'scans_this_week' => $qr->scanEvents()->where('scanned_at', '>=', now()->startOfWeek())->count(),
            'scans_this_month' => $qr->scanEvents()->where('scanned_at', '>=', now()->startOfMonth())->count(),
        ];
    }

    public function edit(QrCode $qrCode)
    {
        $this->authorize('update', $qrCode);

        return Inertia::render('qr/edit', [
            'qr' => $this->presentQr($qrCode),
        ]);
    }

    public function update(UpdateQrCodeRequest $request, QrCode $qrCode, UrlSafetyService $urlSafety)
    {
        $this->authorize('update', $qrCode);

        $data = ['name' => $request->validated('name')];

        if ($qrCode->is_dynamic) {
            $content = $request->validated('content');

            $destination = $this->contentBuilder->destinationUrl($qrCode->type, $content);

            if ($destination && ($reason = $urlSafety->check($destination))) {
                return back()->withErrors(['content.url' => $reason])->withInput();
            }

            $data['content'] = $content;
            $data['destination_url'] = $destination;
        }

        $qrCode->update($data);

        AuditLog::record('qr.updated', $qrCode);

        return redirect()->route('qr.show', $qrCode)->with('success', 'QR code updated.');
    }

    public function destroy(QrCode $qrCode)
    {
        $this->authorize('delete', $qrCode);

        $qrCode->delete();

        AuditLog::record('qr.deleted', $qrCode);

        return redirect()->route('qr.index')->with('success', 'QR code deleted.');
    }

    public function toggleStatus(QrCode $qrCode)
    {
        $this->authorize('update', $qrCode);

        $qrCode->update([
            'status' => $qrCode->status === QrStatus::Active ? QrStatus::Paused : QrStatus::Active,
        ]);

        AuditLog::record('qr.status_toggled', $qrCode, ['status' => $qrCode->status->value]);

        return back()->with('success', $qrCode->status === QrStatus::Active ? 'QR activated.' : 'QR paused.');
    }

    public function download(Request $request, QrCode $qrCode)
    {
        $this->authorize('view', $qrCode);

        $format = $request->query('format', 'png');
        $size = (int) $request->query('size', 512);

        abort_unless(in_array($format, QrImageService::FORMATS, true), 400);
        abort_unless(in_array($size, QrImageService::SIZES, true), 400);

        if ($format === 'svg' && ! $this->planLimits->can($request->user(), 'svg_download')) {
            abort(403, 'SVG download is available on Pro plans.');
        }

        $payload = $this->contentBuilder->payloadFor($qrCode);
        $binary = $this->imageService->render($payload, $format, $size);
        $filename = Str::slug($qrCode->name).'.'.$format;

        return response($binary)
            ->header('Content-Type', $this->imageService->mimeType($format))
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    private function presentQr(QrCode $qr): array
    {
        return [
            'id' => $qr->id,
            'name' => $qr->name,
            'type' => $qr->type->value,
            'type_label' => $qr->type->label(),
            'content' => $qr->content,
            'is_dynamic' => $qr->is_dynamic,
            'status' => $qr->status->value,
            'admin_locked' => $qr->admin_locked,
            'frozen' => $qr->frozen,
            'scan_count' => $qr->scan_count,
            'last_scanned_at' => $qr->last_scanned_at?->toDayDateTimeString(),
            'payload' => $this->contentBuilder->payloadFor($qr),
            'redirect_url' => $qr->is_dynamic ? $qr->redirectUrl() : null,
            'can_svg' => $this->planLimits->can($qr->user, 'svg_download'),
            'created_at' => $qr->created_at->toDayDateTimeString(),
        ];
    }
}
