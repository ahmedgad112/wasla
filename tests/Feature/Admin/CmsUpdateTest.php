<?php

namespace Tests\Feature\Admin;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CmsUpdateTest extends TestCase
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

    public function test_cms_page_receives_text_values_for_the_landing_fields(): void
    {
        SystemSetting::query()->create([
            'key' => 'platform_name_ar',
            'value' => 'وصلة',
            'group' => 'cms',
            'type' => 'string',
        ]);

        $this->actingAs($this->adminUser())
            ->get('/admin/cms')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Cms/Index')
                ->where('settings.platform_name_ar', 'وصلة')
                ->where('settings.hero_subtitle', ''));
    }

    public function test_cms_update_persists_the_landing_page_fields(): void
    {
        $payload = [
            'platform_name_ar' => 'وصلة الجديدة',
            'platform_name_en' => 'Wasla',
            'hero_title' => 'عنوان جديد',
            'hero_subtitle' => 'وصف جديد',
            'city_badge' => 'برج العرب',
            'student_banner_title' => 'خصم الطلاب',
            'contact_phone' => '01011111111',
            'contact_whatsapp' => '201011111111',
            'contact_email' => 'support@example.com',
            'footer_description' => 'وصف الفوتر',
        ];

        $this->actingAs($this->adminUser())
            ->put('/admin/cms', $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('وصلة الجديدة', SystemSetting::query()->where('key', 'platform_name_ar')->value('value'));
        $this->assertSame('وصف الفوتر', SystemSetting::query()->where('key', 'footer_description')->value('value'));
        $this->assertSame('201011111111', SystemSetting::query()->where('key', 'contact_whatsapp')->value('value'));
    }
}
