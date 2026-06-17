<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlockedDomainRequest;
use App\Http\Requests\Admin\UpdateBlockedDomainRequest;
use App\Models\AuditLog;
use App\Models\BlockedDomain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlockedDomainController extends Controller
{
    public function index(Request $request): Response
    {
        $query = BlockedDomain::query()->latest();

        if ($search = trim($request->string('search')->value())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->whereLike('domain', "%{$search}%", caseSensitive: false)
                    ->orWhereLike('reason', "%{$search}%", caseSensitive: false);
            });
        }

        $domains = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (BlockedDomain $domain): array => [
                'id' => $domain->id,
                'domain' => $domain->domain,
                'reason' => $domain->reason,
                'created_at' => $domain->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('admin/blocked-domains/index', [
            'domains' => $domains,
            'filters' => $request->only(['search']),
            'totalDomains' => BlockedDomain::count(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/blocked-domains/create');
    }

    public function store(StoreBlockedDomainRequest $request): RedirectResponse
    {
        $domain = BlockedDomain::create($request->validated());

        AuditLog::record('blocked_domain.created', $domain, [], $request->user()?->id);

        return redirect()
            ->route('admin.blocked-domains.index')
            ->with('success', 'Blocked domain added.');
    }

    public function edit(BlockedDomain $blockedDomain): Response
    {
        return Inertia::render('admin/blocked-domains/edit', [
            'domain' => [
                'id' => $blockedDomain->id,
                'domain' => $blockedDomain->domain,
                'reason' => $blockedDomain->reason,
            ],
        ]);
    }

    public function update(UpdateBlockedDomainRequest $request, BlockedDomain $blockedDomain): RedirectResponse
    {
        $blockedDomain->update($request->validated());

        AuditLog::record('blocked_domain.updated', $blockedDomain, [], $request->user()?->id);

        return redirect()
            ->route('admin.blocked-domains.index')
            ->with('success', 'Blocked domain updated.');
    }

    public function destroy(Request $request, BlockedDomain $blockedDomain): RedirectResponse
    {
        $blockedDomain->delete();

        AuditLog::record('blocked_domain.deleted', $blockedDomain, [
            'domain' => $blockedDomain->domain,
        ], $request->user()?->id);

        return redirect()
            ->route('admin.blocked-domains.index')
            ->with('success', 'Blocked domain removed.');
    }
}
