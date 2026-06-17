<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBrandingRequest;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BrandingController extends Controller
{
    public function edit(): Response
    {
        $logoPath = Setting::get('logo_path');
        $faviconPath = Setting::get('favicon_path');

        return Inertia::render('admin/settings/branding', [
            'branding' => [
                'project_name' => Setting::get('project_name', config('app.name')),
                'seo_title' => Setting::get('seo_title', ''),
                'seo_description' => Setting::get('seo_description', ''),
                'logo_url' => $logoPath ? Storage::disk('public')->url($logoPath) : null,
                'favicon_url' => $faviconPath ? Storage::disk('public')->url($faviconPath) : null,
            ],
        ]);
    }

    public function update(UpdateBrandingRequest $request): RedirectResponse
    {
        Setting::set('project_name', $request->validated('project_name'));
        Setting::set('seo_title', $request->validated('seo_title'));
        Setting::set('seo_description', $request->validated('seo_description'));

        if ($request->boolean('remove_logo')) {
            $this->deleteStoredFile(Setting::get('logo_path'));
            Setting::set('logo_path', null);
        }

        if ($request->boolean('remove_favicon')) {
            $this->deleteStoredFile(Setting::get('favicon_path'));
            Setting::set('favicon_path', null);
        }

        if ($request->hasFile('logo')) {
            $this->deleteStoredFile(Setting::get('logo_path'));
            Setting::set('logo_path', $request->file('logo')->store('branding', 'public'));
        }

        if ($request->hasFile('favicon')) {
            $this->deleteStoredFile(Setting::get('favicon_path'));
            Setting::set('favicon_path', $request->file('favicon')->store('branding', 'public'));
        }

        AuditLog::record('settings.branding_updated', null, [], $request->user()?->id);

        return back()->with('success', 'Branding and SEO settings saved.');
    }

    private function deleteStoredFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
