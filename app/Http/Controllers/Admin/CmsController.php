<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use App\Services\PublicCatalogCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class CmsController extends Controller
{
    public function index(): Response
    {
        $stored = SystemSetting::query()
            ->whereIn('key', $this->contentKeys())
            ->pluck('value', 'key');

        $settings = [];
        foreach ($this->contentKeys() as $key) {
            $settings[$key] = (string) ($stored[$key] ?? '');
        }

        return Inertia::render('Admin/Cms/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'platform_name_ar' => ['nullable', 'string', 'max:255'],
            'platform_name_en' => ['nullable', 'string', 'max:255'],
            'hero_title' => ['nullable', 'string', 'max:255'],
            'hero_subtitle' => ['nullable', 'string', 'max:1000'],
            'city_badge' => ['nullable', 'string', 'max:255'],
            'student_banner_title' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'contact_whatsapp' => ['nullable', 'string', 'max:30'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'footer_description' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ($this->contentKeys() as $key) {
            $this->persistSetting($key, $validated[$key] ?? '');
        }

        PublicCatalogCache::forgetListing();
        ActivityLog::log('CMS_SETTINGS_UPDATED', null, null);

        return back()->with('success', 'تم تحديث إعدادات الموقع بنجاح.');
    }

    /**
     * @return list<string>
     */
    private function contentKeys(): array
    {
        return [
            'platform_name_ar',
            'platform_name_en',
            'hero_title',
            'hero_subtitle',
            'city_badge',
            'student_banner_title',
            'contact_phone',
            'contact_whatsapp',
            'contact_email',
            'footer_description',
        ];
    }

    private function persistSetting(string $key, mixed $value): void
    {
        SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value === null ? '' : (string) $value,
                'group' => 'cms',
                'type' => 'string',
            ]
        );

        Cache::forget("setting_{$key}");
        Cache::forget("system_setting.{$key}");
    }
}
