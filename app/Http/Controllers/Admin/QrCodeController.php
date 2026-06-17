<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QrStatus;
use App\Enums\QrType;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\QrCode;
use App\Services\QrContentBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QrCodeController extends Controller
{
    public function __construct(
        private readonly QrContentBuilder $contentBuilder,
    ) {}

    public function index(Request $request): Response
    {
        $query = QrCode::query()
            ->with('user:id,name,email')
            ->withTrashed()
            ->latest();

        if ($search = trim($request->string('search')->value())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->whereLike('name', "%{$search}%", caseSensitive: false)
                    ->orWhereLike('slug', "%{$search}%", caseSensitive: false)
                    ->orWhereHas('user', fn ($userQuery) => $userQuery
                        ->whereLike('email', "%{$search}%", caseSensitive: false)
                        ->orWhereLike('name', "%{$search}%", caseSensitive: false));
            });
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

        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        $qrCodes = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (QrCode $qr): array => [
                'id' => $qr->id,
                'name' => $qr->name,
                'slug' => $qr->slug,
                'type' => $qr->type->value,
                'type_label' => $qr->type->label(),
                'is_dynamic' => $qr->is_dynamic,
                'status' => $qr->status->value,
                'admin_locked' => $qr->admin_locked,
                'frozen' => $qr->frozen,
                'scan_count' => $qr->scan_count,
                'owner' => $qr->user ? [
                    'id' => $qr->user->id,
                    'name' => $qr->user->name,
                    'email' => $qr->user->email,
                ] : null,
                'created_at' => $qr->created_at?->toDateTimeString(),
                'deleted_at' => $qr->deleted_at?->toDateTimeString(),
            ]);

        return Inertia::render('admin/qr-codes/index', [
            'qrCodes' => $qrCodes,
            'filters' => $request->only(['search', 'type', 'status', 'dynamic', 'trashed']),
            'totalQrCodes' => QrCode::withTrashed()->count(),
            'typeOptions' => collect(QrType::cases())->map(fn (QrType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->values(),
        ]);
    }

    public function show(int $qrCode): Response
    {
        $qr = QrCode::withTrashed()
            ->with('user:id,name,email')
            ->findOrFail($qrCode);

        return Inertia::render('admin/qr-codes/show', [
            'qr' => [
                'id' => $qr->id,
                'name' => $qr->name,
                'slug' => $qr->slug,
                'type' => $qr->type->value,
                'type_label' => $qr->type->label(),
                'content' => $qr->content,
                'destination_url' => $qr->destination_url,
                'payload' => $this->contentBuilder->payloadFor($qr),
                'redirect_url' => $qr->is_dynamic ? $qr->redirectUrl() : null,
                'is_dynamic' => $qr->is_dynamic,
                'status' => $qr->status->value,
                'admin_locked' => $qr->admin_locked,
                'frozen' => $qr->frozen,
                'scan_count' => $qr->scan_count,
                'last_scanned_at' => $qr->last_scanned_at?->toDateTimeString(),
                'expires_at' => $qr->expires_at?->toDateTimeString(),
                'created_at' => $qr->created_at?->toDateTimeString(),
                'updated_at' => $qr->updated_at?->toDateTimeString(),
                'deleted_at' => $qr->deleted_at?->toDateTimeString(),
                'owner' => $qr->user ? [
                    'id' => $qr->user->id,
                    'name' => $qr->user->name,
                    'email' => $qr->user->email,
                ] : null,
            ],
            'canPause' => $qr->status === QrStatus::Active && ! $qr->admin_locked && $qr->deleted_at === null,
        ]);
    }

    public function pause(Request $request, int $qrCode): RedirectResponse
    {
        $qr = QrCode::withTrashed()->findOrFail($qrCode);

        abort_if($qr->deleted_at !== null, 404);
        abort_if($qr->status !== QrStatus::Active || $qr->admin_locked, 403);

        $qr->update([
            'status' => QrStatus::Paused,
            'admin_locked' => true,
        ]);

        AuditLog::record('qr.admin_paused', $qr, [
            'paused_by' => $request->user()?->id,
        ], $request->user()?->id);

        return back()->with('success', 'QR code paused and locked. The owner cannot reactivate it.');
    }
}
