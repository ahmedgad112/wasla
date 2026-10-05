<?php

namespace Tests\Feature\Admin;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingsUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        Role::findOrCreate('SUPER_ADMIN', 'web');

        $admin = User::factory()->create([
            'role' => 'SUPER_ADMIN',
            'is_active' => true,
        ]);
        $admin->assignRole('SUPER_ADMIN');

        return $admin;
    }

    public function test_settings_page_shows_the_saved_slogan(): void
    {
        SystemSetting::query()->create([
            'key' => 'app_slogan',
            'value' => 'أكلك من برة',
            'group' => 'branding',
            'type' => 'string',
        ]);

        $this->actingAs($this->adminUser())
            ->get('/admin/settings')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Settings/Index')
                ->where('settings.app_tagline', 'أكلك من برة')
                ->where('settings.app_name', 'وصلة'));
    }

    public function test_settings_update_persists_the_form_fields(): void
    {
        $this->actingAs($this->adminUser())
            ->put('/admin/settings', [
                'app_name' => 'وصلة',
                'app_tagline' => 'توصيل أسرع',
                'support_email' => 'support@example.com',
                'support_phone' => '01027961208',
                'default_commission_rate' => '12',
                'default_tax_rate' => '14',
                'default_delivery_fee' => '15',
                'order_auto_cancel_minutes' => '45',
                'maintenance_mode' => '0',
                'allow_registrations' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('توصيل أسرع', SystemSetting::query()->where('key', 'app_tagline')->value('value'));
        $this->assertSame('توصيل أسرع', SystemSetting::query()->where('key', 'app_slogan')->value('value'));
        $this->assertSame('15', SystemSetting::query()->where('key', 'default_delivery_fee')->value('value'));
        $this->assertSame('01027961208', SystemSetting::query()->where('key', 'support_phone')->value('value'));
        $this->assertSame('وصلة', SystemSetting::query()->where('key', 'platform_name_ar')->value('value'));
    }

    public function test_settings_update_controls_public_site_content(): void
    {
        $this->actingAs($this->adminUser())
            ->put('/admin/settings', [
                'app_name' => 'وصلة',
                'platform_name_en' => 'Wasla',
                'app_tagline' => 'أكلك من برة',
                'hero_title' => 'عنوان الصفحة',
                'hero_subtitle' => 'وصف الصفحة',
                'home_headline' => 'اطلب دلوقتي',
                'search_placeholder' => 'دور على أكلة',
                'city_badge' => 'برج العرب',
                'student_banner_title' => 'خصم الطلاب',
                'footer_description' => 'وصف الفوتر',
                'meta_title' => 'وصلة',
                'meta_description' => 'وصف',
                'support_email' => 'support@example.com',
                'support_phone' => '01027961208',
                'contact_phone' => '01011111111',
                'contact_whatsapp' => '201011111111',
                'contact_email' => 'hello@example.com',
                'office_address' => 'برج العرب',
                'working_hours' => 'طوال اليوم',
                'default_commission_rate' => '12',
                'default_tax_rate' => '14',
                'default_delivery_fee' => '15',
                'minimum_order_amount' => '50',
                'order_auto_cancel_minutes' => '45',
                'maintenance_mode' => '0',
                'allow_registrations' => '0',
                'show_offers_section' => '0',
                'show_restaurants_section' => '1',
                'show_leaderboard_section' => '1',
                'show_stats_section' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('عنوان الصفحة', SystemSetting::query()->where('key', 'hero_title')->value('value'));
        $this->assertSame('عنوان الصفحة', SystemSetting::query()->where('key', 'home_headline')->value('value'));
        $this->assertSame('برج العرب', SystemSetting::query()->where('key', 'city_badge')->value('value'));
        $this->assertSame('0', SystemSetting::query()->where('key', 'allow_registrations')->value('value'));
        $this->assertSame('201011111111', SystemSetting::query()->where('key', 'whatsapp_number')->value('value'));
    }

    public function test_clearing_a_homepage_field_does_not_restore_the_default(): void
    {
        SystemSetting::query()->create([
            'key' => 'city_badge',
            'value' => 'جامعة برج العرب',
            'group' => 'homepage',
            'type' => 'string',
        ]);

        $this->actingAs($this->adminUser())
            ->put('/admin/settings', [
                'app_name' => 'وصلة',
                'hero_title' => 'عنوان جديد',
                'city_badge' => '',
                'student_banner_title' => '',
                'maintenance_mode' => '0',
                'allow_registrations' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('', SystemSetting::query()->where('key', 'city_badge')->value('value'));
        $this->assertSame('', SystemSetting::query()->where('key', 'student_banner_title')->value('value'));
        $this->assertSame('عنوان جديد', SystemSetting::query()->where('key', 'hero_title')->value('value'));
    }
}
