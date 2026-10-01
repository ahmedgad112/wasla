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
    }
}
