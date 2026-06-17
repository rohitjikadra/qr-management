<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    private const BRANDING_KEYS = [
        'project_name',
        'seo_title',
        'seo_description',
        'logo_path',
        'favicon_path',
    ];

    public function index(): Response
    {
        $settings = Setting::query()
            ->whereNotIn('key', self::BRANDING_KEYS)
            ->orderBy('key')
            ->get()
            ->map(fn (Setting $setting) => [
                'key' => $setting->key,
                'value' => $setting->value,
            ])
            ->values();

        return Inertia::render('admin/settings/index', [
            'settings' => $settings,
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        foreach ($request->validated('settings') as $row) {
            $key = $row['key'];

            if (in_array($key, self::BRANDING_KEYS, true)) {
                continue;
            }

            Setting::set($key, $row['value'] ?? null);
        }

        AuditLog::record('settings.updated', null, [
            'keys' => collect($request->validated('settings'))->pluck('key')->all(),
        ], $request->user()?->id);

        return back()->with('success', 'Settings saved.');
    }
}
