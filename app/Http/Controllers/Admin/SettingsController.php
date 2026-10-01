<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        $stored = SystemSetting::query()->pluck('value', 'key');

        return Inertia::render('Admin/Settings/Index', [
            'settings' => [
                'app_name' => (string) ($stored['app_name'] ?? 'وصلة'),
                'app_tagline' => (string) ($stored['app_tagline'] ?? $stored['app_slogan'] ?? ''),
                'support_email' => (string) ($stored['support_email'] ?? ''),
                'support_phone' => (string) ($stored['support_phone'] ?? ''),
                'default_commission_rate' => (string) ($stored['default_commission_rate'] ?? '15'),
                'default_tax_rate' => (string) ($stored['default_tax_rate'] ?? '14'),
                'default_delivery_fee' => (string) ($stored['default_delivery_fee'] ?? '10.00'),
                'order_auto_cancel_minutes' => (string) ($stored['order_auto_cancel_minutes'] ?? '30'),
                'maintenance_mode' => (string) ($stored['maintenance_mode'] ?? '0'),
                'allow_registrations' => (string) ($stored['allow_registrations'] ?? '1'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:255'],
            'app_tagline' => ['nullable', 'string', 'max:255'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:30'],
            'default_commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'default_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'default_delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'order_auto_cancel_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'maintenance_mode' => ['required', 'in:0,1'],
            'allow_registrations' => ['required', 'in:0,1'],
        ]);

        $this->persistSetting('app_name', $validated['app_name'], 'branding');
        $this->persistSetting('app_tagline', $validated['app_tagline'] ?? '', 'branding');
        $this->persistSetting('app_slogan', $validated['app_tagline'] ?? '', 'branding');
        $this->persistSetting('support_email', $validated['support_email'] ?? '', 'support');
        $this->persistSetting('support_phone', $validated['support_phone'] ?? '', 'support');
        $this->persistSetting('default_commission_rate', $validated['default_commission_rate'] ?? '15', 'finance');
        $this->persistSetting('default_tax_rate', $validated['default_tax_rate'] ?? '14', 'finance');
        $this->persistSetting('default_delivery_fee', $validated['default_delivery_fee'] ?? '10.00', 'finance');
        $this->persistSetting('order_auto_cancel_minutes', $validated['order_auto_cancel_minutes'] ?? '30', 'orders', 'integer');
        $this->persistSetting('maintenance_mode', $validated['maintenance_mode'], 'system', 'boolean');
        $this->persistSetting('allow_registrations', $validated['allow_registrations'], 'system', 'boolean');

        ActivityLog::log('SYSTEM_SETTINGS_UPDATED', null, null);

        return back()->with('success', 'تم حفظ الإعدادات.');
    }

    private function persistSetting(string $key, mixed $value, string $group, string $type = 'string'): void
    {
        SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value === null ? '' : (string) $value,
                'group' => $group,
                'type' => $type,
            ]
        );

        Cache::forget("setting_{$key}");
        Cache::forget("system_setting.{$key}");
    }
}
