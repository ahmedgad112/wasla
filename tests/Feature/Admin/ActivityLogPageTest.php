<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivityLogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_activity_logs(): void
    {
        $this->get('/admin/activity-logs')
            ->assertRedirect(route('login'));
    }

    public function test_customer_is_redirected_from_activity_logs(): void
    {
        $customer = User::factory()->create([
            'role' => 'CUSTOMER',
            'is_active' => true,
        ]);

        $this->actingAs($customer)
            ->get('/admin/activity-logs')
            ->assertRedirect(route('login'));
    }

    public function test_staff_without_permission_is_forbidden_from_activity_logs(): void
    {
        $staff = User::factory()->create([
            'role' => 'PLATFORM_STAFF',
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->get('/admin/activity-logs')
            ->assertForbidden();
    }

    public function test_admin_activity_log_page_lists_actor_and_action(): void
    {
        $admin = $this->adminUser();

        ActivityLog::query()->create([
            'user_id' => $admin->id,
            'action' => 'USER_LOGIN',
            'entity_type' => 'User',
            'entity_id' => $admin->id,
            'new_values' => ['portal' => 'admin'],
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($admin)
            ->get('/admin/activity-logs')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/ActivityLogs/Index')
                ->where('logs.total', 1)
                ->where('logs.data.0.action', 'USER_LOGIN')
                ->where('logs.data.0.user.name', $admin->name)
                ->where('logs.data.0.entity_type', 'User')
                ->where('actions.0', 'USER_LOGIN'));
    }

    public function test_activity_log_search_matches_actor_name(): void
    {
        $admin = $this->adminUser();
        $other = User::factory()->create([
            'name' => 'منى التوصيل',
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        ActivityLog::query()->create([
            'user_id' => $other->id,
            'action' => 'ORDER_CREATED',
            'entity_type' => 'Order',
            'entity_id' => 15,
        ]);
        ActivityLog::query()->create([
            'user_id' => $admin->id,
            'action' => 'USER_LOGIN',
            'entity_type' => 'User',
            'entity_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get('/admin/activity-logs?search='.urlencode('منى التوصيل'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('logs.total', 1)
                ->where('logs.data.0.action', 'ORDER_CREATED')
                ->where('logs.data.0.user.name', 'منى التوصيل'));
    }

    public function test_activity_log_filters_by_action_and_date(): void
    {
        $admin = $this->adminUser();

        $oldLogin = ActivityLog::query()->create([
            'user_id' => $admin->id,
            'action' => 'USER_LOGIN',
            'entity_type' => 'User',
            'entity_id' => $admin->id,
        ]);
        $oldLogin->forceFill(['created_at' => '2026-01-05 09:00:00'])->save();

        $recentOrder = ActivityLog::query()->create([
            'user_id' => $admin->id,
            'action' => 'ORDER_CREATED',
            'entity_type' => 'Order',
            'entity_id' => 8,
        ]);
        $recentOrder->forceFill(['created_at' => '2026-10-01 12:00:00'])->save();

        $this->actingAs($admin)
            ->get('/admin/activity-logs?action=ORDER_CREATED&date_from=2026-09-01&date_to=2026-10-01')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('logs.total', 1)
                ->where('logs.data.0.action', 'ORDER_CREATED')
                ->where('logs.data.0.entity_id', 8));
    }

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
}
