<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminTeamMemberRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminTeamController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::query()
            ->whereIn('role', [UserRole::Admin, UserRole::SuperAdmin])
            ->latest();

        if ($search = trim($request->string('search')->value())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->whereLike('name', "%{$search}%", caseSensitive: false)
                    ->orWhereLike('email', "%{$search}%", caseSensitive: false);
            });
        }

        if ($role = $request->string('role')->value()) {
            $query->where('role', $role);
        }

        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        $members = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'status' => $user->status->value,
                'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
                'last_login_at' => $user->last_login_at?->toDateTimeString(),
                'created_at' => $user->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('admin/team/index', [
            'members' => $members,
            'filters' => $request->only(['search', 'role', 'status']),
            'totalMembers' => User::query()
                ->whereIn('role', [UserRole::Admin, UserRole::SuperAdmin])
                ->count(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/team/create');
    }

    public function store(StoreAdminTeamMemberRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $markVerified = (bool) $data['email_verified'];
        unset($data['email_verified']);

        $admin = User::create([
            ...$data,
            'role' => UserRole::Admin,
            'email_verified_at' => $markVerified ? now() : null,
        ]);

        AuditLog::record('admin.created', $admin, [
            'created_by' => $request->user()?->id,
            'role' => UserRole::Admin->value,
        ], $request->user()?->id);

        return redirect()
            ->route('admin.team.index')
            ->with('success', 'Admin account created successfully.');
    }
}
