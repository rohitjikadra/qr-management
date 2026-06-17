<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AuditLog::query()
            ->with('user:id,name,email')
            ->latest('created_at');

        if ($action = trim($request->string('action')->value())) {
            $query->whereLike('action', "%{$action}%", caseSensitive: false);
        }

        if ($userId = $request->integer('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($from = $request->string('from')->value()) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->string('to')->value()) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs = $query
            ->paginate(30)
            ->withQueryString()
            ->through(fn (AuditLog $log): array => [
                'id' => $log->id,
                'action' => $log->action,
                'entity_type' => $log->entity_type,
                'entity_id' => $log->entity_id,
                'created_at' => $log->created_at?->toDateTimeString(),
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'email' => $log->user->email,
                ] : null,
            ]);

        return Inertia::render('admin/audit-logs/index', [
            'logs' => $logs,
            'filters' => $request->only(['action', 'user_id', 'from', 'to']),
            'totalLogs' => AuditLog::count(),
        ]);
    }

    public function show(AuditLog $auditLog): Response
    {
        $auditLog->load('user:id,name,email');

        return Inertia::render('admin/audit-logs/show', [
            'log' => [
                'id' => $auditLog->id,
                'action' => $auditLog->action,
                'entity_type' => $auditLog->entity_type,
                'entity_id' => $auditLog->entity_id,
                'meta' => $auditLog->meta,
                'meta_json' => json_encode($auditLog->meta ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                'created_at' => $auditLog->created_at?->toDateTimeString(),
                'user' => $auditLog->user ? [
                    'id' => $auditLog->user->id,
                    'name' => $auditLog->user->name,
                    'email' => $auditLog->user->email,
                ] : null,
            ],
        ]);
    }
}
