<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\BillingActions;
use App\Enums\BillingCycle;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GrantComplimentaryRequest;
use App\Http\Requests\Admin\SetBillingDiscountRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::query()
            ->where('role', UserRole::User)
            ->withCount('qrCodes')
            ->latest();

        if ($search = trim($request->string('search')->value())) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->whereLike('name', "%{$search}%", caseSensitive: false)
                    ->orWhereLike('email', "%{$search}%", caseSensitive: false);
            });
        }

        if ($status = $request->string('status')->value()) {
            $query->where('status', $status);
        }

        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        $users = $query
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'status' => $user->status->value,
                'billing_discount_percent' => $user->billing_discount_percent,
                'qr_codes_count' => $user->qr_codes_count,
                'created_at' => $user->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => $request->only(['search', 'status', 'trashed']),
            'totalUsers' => User::query()->where('role', UserRole::User)->count(),
        ]);
    }

    public function show(User $user, Request $request): Response
    {
        $user->loadCount('qrCodes', 'subscriptions', 'payments')
            ->load([
                'subscriptions' => fn ($query) => $query->with('plan')->latest('starts_at')->limit(100),
                'qrCodes' => fn ($query) => $query->latest()->limit(100),
                'payments' => fn ($query) => $query->latest()->limit(100),
            ]);

        $activeSubscription = $user->subscriptions
            ->first(fn ($subscription) => $subscription->status->grantsProAccess());

        return Inertia::render('admin/users/show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'status' => $user->status->value,
                'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
                'billing_discount_percent' => $user->billing_discount_percent,
                'billing_note' => $user->billing_note,
                'country' => $user->country,
                'last_login_at' => $user->last_login_at?->toDateTimeString(),
                'created_at' => $user->created_at?->toDateTimeString(),
                'updated_at' => $user->updated_at?->toDateTimeString(),
                'deleted_at' => $user->deleted_at?->toDateTimeString(),
                'qr_codes_count' => $user->qr_codes_count,
                'subscriptions_count' => $user->subscriptions_count,
                'payments_count' => $user->payments_count,
            ],
            'activeSubscription' => $activeSubscription ? [
                'id' => $activeSubscription->id,
                'plan_name' => $activeSubscription->plan->name,
                'status' => $activeSubscription->status->value,
                'gateway' => $activeSubscription->gateway,
                'is_complimentary' => $activeSubscription->is_complimentary,
                'starts_at' => $activeSubscription->starts_at?->toDateTimeString(),
                'expires_at' => $activeSubscription->expires_at?->toDateTimeString(),
            ] : null,
            'recentSubscriptions' => $user->subscriptions->map(fn ($subscription) => [
                'id' => $subscription->id,
                'plan_name' => $subscription->plan->name,
                'status' => $subscription->status->value,
                'gateway' => $subscription->gateway,
                'is_complimentary' => $subscription->is_complimentary,
                'starts_at' => $subscription->starts_at?->toDateTimeString(),
                'expires_at' => $subscription->expires_at?->toDateTimeString(),
            ])->values(),
            'subscriptions' => $user->subscriptions->map(fn ($subscription) => [
                'id' => $subscription->id,
                'plan_name' => $subscription->plan->name,
                'status' => $subscription->status->value,
                'gateway' => $subscription->gateway,
                'is_complimentary' => $subscription->is_complimentary,
                'starts_at' => $subscription->starts_at?->toDateTimeString(),
                'expires_at' => $subscription->expires_at?->toDateTimeString(),
            ])->values(),
            'recentQrCodes' => $user->qrCodes->map(fn ($qr) => [
                'id' => $qr->id,
                'name' => $qr->name,
                'type' => $qr->type->value,
                'status' => $qr->status->value,
                'scan_count' => $qr->scan_count,
                'created_at' => $qr->created_at?->toDateTimeString(),
            ])->values(),
            'qrCodes' => $user->qrCodes->map(fn ($qr) => [
                'id' => $qr->id,
                'name' => $qr->name,
                'type' => $qr->type->value,
                'type_label' => $qr->type->label(),
                'status' => $qr->status->value,
                'scan_count' => $qr->scan_count,
                'is_dynamic' => $qr->is_dynamic,
                'created_at' => $qr->created_at?->toDateTimeString(),
            ])->values(),
            'payments' => $user->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'invoice_number' => $payment->invoice_number,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'status' => $payment->status->value,
                'paid_at' => $payment->paid_at?->toDateTimeString(),
            ])->values(),
            'canBan' => Gate::forUser($request->user())->allows('ban', $user),
            'canUnban' => $user->status === UserStatus::Banned && Gate::forUser($request->user())->allows('ban', $user),
            'canImpersonate' => Gate::forUser($request->user())->allows('impersonate', $user),
            'canDelete' => Gate::forUser($request->user())->allows('delete', $user),
            'canResendVerification' => $user->email_verified_at === null,
            'complimentaryPlans' => Plan::query()
                ->where('is_active', true)
                ->where('billing_cycle', '!=', BillingCycle::Free)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Plan $plan) => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                ])
                ->values(),
        ]);
    }

    public function edit(User $user, Request $request): Response
    {
        return Inertia::render('admin/users/edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'status' => $user->status->value,
                'country' => $user->country,
                'billing_discount_percent' => $user->billing_discount_percent,
                'billing_note' => $user->billing_note,
                'email_verified' => $user->hasVerifiedEmail(),
                'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
            ],
            'canEditRole' => $request->user()?->role === UserRole::SuperAdmin,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if ($request->user()?->role !== UserRole::SuperAdmin) {
            unset($data['role']);
        }

        $markVerified = (bool) $data['email_verified'];
        unset($data['email_verified']);

        $emailChanged = ($data['email'] ?? $user->email) !== $user->email;

        if ($markVerified) {
            $data['email_verified_at'] = ($emailChanged || $user->email_verified_at === null)
                ? now()
                : $user->email_verified_at;
        } else {
            $data['email_verified_at'] = null;
        }

        $user->update($data);

        AuditLog::record('user.updated', $user, [
            'updated_by' => $request->user()?->id,
            'fields' => array_keys($data),
        ], $request->user()?->id);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    public function ban(Request $request, User $user): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('ban', $user);

        if ($user->status === UserStatus::Banned) {
            return back()->with('success', 'User is already banned.');
        }

        $user->update(['status' => UserStatus::Banned]);

        AuditLog::record('user.banned', $user, [
            'banned_by' => $request->user()?->id,
        ], $request->user()?->id);

        return back()->with('success', 'User banned successfully.');
    }

    public function unban(Request $request, User $user): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('ban', $user);

        if ($user->status === UserStatus::Active) {
            return back()->with('success', 'User is already active.');
        }

        $user->update(['status' => UserStatus::Active]);

        AuditLog::record('user.unbanned', $user, [
            'unbanned_by' => $request->user()?->id,
        ], $request->user()?->id);

        return back()->with('success', 'User unbanned successfully.');
    }

    public function setDiscount(SetBillingDiscountRequest $request, User $user, BillingActions $billing): RedirectResponse
    {
        $validated = $request->validated();

        $percent = $validated['billing_discount_percent'] ?? null;
        $percent = $percent !== null ? (int) $percent : null;

        $billing->setBillingDiscount(
            $user,
            $percent,
            $validated['billing_note'] ?? null,
            $request->user(),
        );

        return back()->with('success', 'Billing discount updated.');
    }

    public function grantComplimentary(
        GrantComplimentaryRequest $request,
        User $user,
        BillingActions $billing,
    ): RedirectResponse {
        $validated = $request->validated();
        $plan = Plan::findOrFail($validated['plan_id']);

        $subscription = $billing->grantComplimentary(
            $user,
            $plan,
            (int) $validated['duration_days'],
            $validated['admin_note'],
            $request->user(),
        );

        return back()->with(
            'success',
            "Complimentary {$plan->name} granted until {$subscription->expires_at?->toFormattedDateString()}."
        );
    }

    public function impersonate(Request $request, User $user): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('impersonate', $user);

        $impersonatorId = $request->user()->id;
        $request->session()->put('impersonator_id', $impersonatorId);

        Auth::login($user);
        $request->session()->regenerate();

        AuditLog::record('user.impersonated', $user, [
            'impersonator_id' => $impersonatorId,
        ], $impersonatorId);

        return redirect()
            ->route('dashboard')
            ->with('success', "Now impersonating {$user->email}.");
    }

    public function resendVerification(Request $request, User $user): RedirectResponse
    {
        if ($user->hasVerifiedEmail()) {
            return back()->with('success', 'User email is already verified.');
        }

        $user->sendEmailVerificationNotification();

        AuditLog::record('user.verification_resent', $user, [
            'resent_by' => $request->user()?->id,
        ], $request->user()?->id);

        return back()->with('success', 'Verification email sent.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('delete', $user);

        $user->delete();

        AuditLog::record('user.deleted', $user, [
            'deleted_by' => $request->user()?->id,
        ], $request->user()?->id);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function export(): StreamedResponse
    {
        $filename = 'users-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'id', 'name', 'email', 'role', 'status', 'email_verified_at',
                'billing_discount_percent', 'qr_codes_count', 'created_at',
            ]);

            User::query()
                ->where('role', UserRole::User)
                ->withCount('qrCodes')
                ->orderBy('id')
                ->chunk(200, function ($users) use ($handle): void {
                    foreach ($users as $user) {
                        fputcsv($handle, [
                            $user->id,
                            $user->name,
                            $user->email,
                            $user->role->value,
                            $user->status->value,
                            $user->email_verified_at?->toDateTimeString(),
                            $user->billing_discount_percent,
                            $user->qr_codes_count,
                            $user->created_at?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

}
