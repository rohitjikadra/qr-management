<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');
        $user = $request->user()?->fresh();
        $logoPath = Setting::get('logo_path');
        $faviconPath = Setting::get('favicon_path');

        return array_merge(parent::share($request), [
            'name' => Setting::get('project_name', config('app.name')),
            'branding' => [
                'logo_url' => $logoPath ? Storage::disk('public')->url($logoPath) : null,
                'favicon_url' => $faviconPath ? Storage::disk('public')->url($faviconPath) : null,
            ],
            'seo' => [
                'title' => Setting::get('seo_title'),
                'description' => Setting::get('seo_description'),
            ],
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $user,
                'is_super_admin' => $user?->role === \App\Enums\UserRole::SuperAdmin,
            ],
            'billing_discount_percent' => $user?->billing_discount_percent,
            'flash' => [
                'success' => $request->session()->get('success'),
                'checkout' => $request->session()->get('checkout'),
            ],
            'impersonation' => $this->impersonationMeta($request),
        ]);
    }

    /**
     * @return array{admin_id: int, admin_name: string}|null
     */
    private function impersonationMeta(Request $request): ?array
    {
        $impersonatorId = $request->session()->get('impersonator_id');

        if (! $impersonatorId) {
            return null;
        }

        $admin = User::query()->find($impersonatorId);

        if (! $admin) {
            return null;
        }

        return [
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
        ];
    }
}
