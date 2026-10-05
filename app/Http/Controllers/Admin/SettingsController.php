<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => SiteSettings::formValues(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate(SiteSettings::rules());

        foreach (SiteSettings::definitions() as $key => $definition) {
            if (! array_key_exists($key, $validated)) {
                continue;
            }

            $incoming = $validated[$key];
            if ($incoming === null && $definition['type'] !== 'string') {
                $incoming = $definition['default'];
            }

            $this->persistSetting(
                $key,
                $incoming ?? '',
                $definition['group'],
                $definition['type'],
            );
        }

        if (array_key_exists('app_name', $validated)) {
            $this->persistSetting('platform_name_ar', $validated['app_name'] ?? '', 'branding');
        }

        if (array_key_exists('app_tagline', $validated)) {
            $this->persistSetting('app_slogan', $validated['app_tagline'] ?? '', 'branding');
        }

        if (array_key_exists('hero_title', $validated)) {
            $this->persistSetting('home_headline', $validated['hero_title'] ?? '', 'homepage');
        }

        if (array_key_exists('hero_subtitle', $validated)) {
            $this->persistSetting('hero_description', $validated['hero_subtitle'] ?? '', 'homepage');
        }

        if (array_key_exists('contact_whatsapp', $validated)) {
            $this->persistSetting('whatsapp_number', $validated['contact_whatsapp'] ?? '', 'contact');
        }

        SiteSettings::forgetSharedCache();
        ActivityLog::log('SYSTEM_SETTINGS_UPDATED', null, null);

        return back()->with('success', 'تم حفظ إعدادات الموقع.');
    }

    private function persistSetting(string $key, mixed $value, string $group, string $type = 'string'): void
    {
        SystemSetting::set($key, $value === null ? '' : $value, $group, $type);
        Cache::forget("system_setting.{$key}");
    }
}
