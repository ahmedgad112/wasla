<?php

namespace App\Support;

use App\Models\SystemSetting;
use App\Services\PublicCatalogCache;
use Illuminate\Support\Facades\Cache;

class SiteSettings
{
    public const CACHE_KEY = 'system_setting.site';

    /**
     * @return array<string, array{group: string, type: string, default: string, rules: list<string>}>
     */
    public static function definitions(): array
    {
        return [
            'app_name' => self::text('branding', 'وصلة', ['required', 'string', 'max:255']),
            'platform_name_en' => self::text('branding', 'Wasla', ['nullable', 'string', 'max:255']),
            'app_tagline' => self::text('branding', 'أكلك من برة', ['nullable', 'string', 'max:255']),
            'hero_title' => self::text('homepage', 'هتطلب إيه النهاردة؟', ['nullable', 'string', 'max:255']),
            'hero_subtitle' => self::text('homepage', 'اطلب من مطاعمك المفضلة مع توصيل سريع', ['nullable', 'string', 'max:1000']),
            'home_headline' => self::text('homepage', 'هتطلب إيه النهاردة؟', ['nullable', 'string', 'max:255']),
            'search_placeholder' => self::text('homepage', 'ابحث عن مطعم أو وجبة...', ['nullable', 'string', 'max:255']),
            'city_badge' => self::text('homepage', 'جامعة برج العرب', ['nullable', 'string', 'max:255']),
            'offers_section_title' => self::text('homepage', 'عروض النهاردة', ['nullable', 'string', 'max:255']),
            'restaurants_section_title' => self::text('homepage', 'مطاعم قريبة منك', ['nullable', 'string', 'max:255']),
            'leaderboard_section_title' => self::text('homepage', 'الأكثر طلباً', ['nullable', 'string', 'max:255']),
            'student_banner_title' => self::text('homepage', 'خصومات خاصة لطلاب جامعة برج العرب التكنولوجية', ['nullable', 'string', 'max:255']),
            'footer_description' => self::text('homepage', 'منصة وصلة — توصيل الطعام الأسرع في برج العرب والإسكندرية', ['nullable', 'string', 'max:1000']),
            'meta_title' => self::text('seo', 'وصلة | اطلب من مطاعم الجامعة', ['nullable', 'string', 'max:255']),
            'meta_description' => self::text('seo', 'اطلب الفطار والغدا والعشا من مطاعم برج العرب مع توصيل سريع.', ['nullable', 'string', 'max:500']),
            'support_email' => self::text('contact', '', ['nullable', 'email', 'max:255']),
            'support_phone' => self::text('contact', '', ['nullable', 'string', 'max:30']),
            'contact_phone' => self::text('contact', '', ['nullable', 'string', 'max:30']),
            'contact_whatsapp' => self::text('contact', '', ['nullable', 'string', 'max:30']),
            'contact_email' => self::text('contact', '', ['nullable', 'email', 'max:255']),
            'office_address' => self::text('contact', 'مدينة برج العرب الجديدة — بجوار مجمع الجامعات، الإسكندرية', ['nullable', 'string', 'max:500']),
            'working_hours' => self::text('contact', 'يومياً من 6:00 صباحاً حتى 2:00 بعد منتصف الليل', ['nullable', 'string', 'max:255']),
            'facebook_url' => self::text('social', '', ['nullable', 'url', 'max:255']),
            'instagram_url' => self::text('social', '', ['nullable', 'url', 'max:255']),
            'tiktok_url' => self::text('social', '', ['nullable', 'url', 'max:255']),
            'default_commission_rate' => self::text('finance', '15', ['nullable', 'numeric', 'min:0', 'max:100']),
            'default_tax_rate' => self::text('finance', '14', ['nullable', 'numeric', 'min:0', 'max:100']),
            'default_delivery_fee' => self::text('finance', '10.00', ['nullable', 'numeric', 'min:0']),
            'minimum_order_amount' => self::text('finance', '0', ['nullable', 'numeric', 'min:0']),
            'order_auto_cancel_minutes' => self::text('orders', '30', ['nullable', 'integer', 'min:1', 'max:1440'], 'integer'),
            'maintenance_mode' => self::text('system', '0', ['nullable', 'in:0,1'], 'boolean'),
            'allow_registrations' => self::text('system', '1', ['nullable', 'in:0,1'], 'boolean'),
            'show_offers_section' => self::text('visibility', '1', ['nullable', 'in:0,1'], 'boolean'),
            'show_restaurants_section' => self::text('visibility', '1', ['nullable', 'in:0,1'], 'boolean'),
            'show_leaderboard_section' => self::text('visibility', '1', ['nullable', 'in:0,1'], 'boolean'),
            'show_stats_section' => self::text('visibility', '1', ['nullable', 'in:0,1'], 'boolean'),
        ];
    }

    /**
     * @return array<string, list<string>|string>
     */
    public static function rules(): array
    {
        $rules = [];

        foreach (self::definitions() as $key => $definition) {
            $rules[$key] = $definition['rules'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public static function formValues(): array
    {
        $stored = SystemSetting::query()->pluck('value', 'key');
        $values = [];

        foreach (self::definitions() as $key => $definition) {
            $values[$key] = (string) ($stored[$key] ?? $definition['default']);
        }

        if ($values['app_tagline'] === '' && filled($stored['app_slogan'] ?? null)) {
            $values['app_tagline'] = (string) $stored['app_slogan'];
        }

        if ($values['hero_subtitle'] === '' && filled($stored['hero_description'] ?? null)) {
            $values['hero_subtitle'] = (string) $stored['hero_description'];
        }

        if ($values['contact_whatsapp'] === '' && filled($stored['whatsapp_number'] ?? null)) {
            $values['contact_whatsapp'] = (string) $stored['whatsapp_number'];
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    public static function publicPayload(): array
    {
        $values = self::formValues();

        return [
            'app_name' => $values['app_name'],
            'platform_name_en' => $values['platform_name_en'],
            'app_tagline' => $values['app_tagline'],
            'hero_title' => $values['hero_title'],
            'hero_subtitle' => $values['hero_subtitle'],
            'home_headline' => $values['hero_title'],
            'search_placeholder' => $values['search_placeholder'],
            'city_badge' => $values['city_badge'],
            'offers_section_title' => $values['offers_section_title'],
            'restaurants_section_title' => $values['restaurants_section_title'],
            'leaderboard_section_title' => $values['leaderboard_section_title'],
            'student_banner_title' => $values['student_banner_title'],
            'footer_description' => $values['footer_description'],
            'meta_title' => $values['meta_title'],
            'meta_description' => $values['meta_description'],
            'support_email' => $values['support_email'] !== '' ? $values['support_email'] : $values['contact_email'],
            'support_phone' => $values['support_phone'] !== '' ? $values['support_phone'] : $values['contact_phone'],
            'contact_phone' => $values['contact_phone'] !== '' ? $values['contact_phone'] : $values['support_phone'],
            'contact_whatsapp' => $values['contact_whatsapp'],
            'contact_email' => $values['contact_email'] !== '' ? $values['contact_email'] : $values['support_email'],
            'office_address' => $values['office_address'],
            'working_hours' => $values['working_hours'],
            'facebook_url' => $values['facebook_url'],
            'instagram_url' => $values['instagram_url'],
            'tiktok_url' => $values['tiktok_url'],
            'default_delivery_fee' => $values['default_delivery_fee'],
            'minimum_order_amount' => $values['minimum_order_amount'],
            'show_offers_section' => $values['show_offers_section'] === '1',
            'show_restaurants_section' => $values['show_restaurants_section'] === '1',
            'show_leaderboard_section' => $values['show_leaderboard_section'] === '1',
            'show_stats_section' => $values['show_stats_section'] === '1',
            'allow_registrations' => $values['allow_registrations'] === '1',
            'maintenance_mode' => $values['maintenance_mode'] === '1',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function shared(): array
    {
        return self::publicPayload();
    }

    public static function flag(string $key, bool $default = false): bool
    {
        $value = SystemSetting::get($key, $default ? '1' : '0');

        if (is_bool($value)) {
            return $value;
        }

        return (string) $value === '1';
    }

    public static function forgetSharedCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('system_setting.app_name');
        Cache::forget('system_setting.app_slogan');
        Cache::forget('system_setting.support_phone');
        Cache::forget('public.cms_settings');
        Cache::forget('landing_page_payload');
        PublicCatalogCache::forgetListing();
    }

    /**
     * @param  list<string>  $rules
     * @return array{group: string, type: string, default: string, rules: list<string>}
     */
    private static function text(string $group, string $default, array $rules, string $type = 'string'): array
    {
        return [
            'group' => $group,
            'type' => $type,
            'default' => $default,
            'rules' => $rules,
        ];
    }
}
