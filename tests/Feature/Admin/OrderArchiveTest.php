<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_archived_orders_leave_the_active_list_and_can_be_restored(): void
    {
        $admin = $this->admin();
        $restaurant = $this->restaurant();
        $active = $this->order($restaurant, 'FS-ACTIVE');
        $archived = $this->order($restaurant, 'FS-ARCHIVED');

        $this->actingAs($admin)
            ->post(route('admin.orders.archive', $archived))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull($archived->fresh()->archived_at);
        $this->assertNull($active->fresh()->archived_at);

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Orders/Index')
                ->has('orders.data', 1)
                ->where('orders.data.0.order_number', 'FS-ACTIVE'));

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['archive' => 'archived']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('orders.data', 1)
                ->where('orders.data.0.order_number', 'FS-ARCHIVED'));

        $this->actingAs($admin)
            ->post(route('admin.orders.restore', $archived))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNull($archived->fresh()->archived_at);
    }

    public function test_admin_can_archive_several_orders_at_once(): void
    {
        $admin = $this->admin();
        $restaurant = $this->restaurant();
        $first = $this->order($restaurant, 'FS-ONE');
        $second = $this->order($restaurant, 'FS-TWO');

        $this->actingAs($admin)
            ->post(route('admin.orders.archive.bulk'), [
                'ids' => [$first->id, $second->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull($first->fresh()->archived_at);
        $this->assertNotNull($second->fresh()->archived_at);
    }

    public function test_deleted_order_disappears_from_order_lists(): void
    {
        $admin = $this->admin();
        $restaurant = $this->restaurant();
        $order = $this->order($restaurant, 'FS-DELETE');

        $this->actingAs($admin)
            ->delete(route('admin.orders.destroy', $order))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSoftDeleted($order);
        $this->assertNull(Order::query()->find($order->id));
    }

    public function test_staff_with_view_permission_cannot_archive_or_delete_orders(): void
    {
        $staff = $this->staffWithViewOnly();
        $order = $this->order($this->restaurant(), 'FS-LOCKED');

        $this->actingAs($staff)
            ->get(route('admin.orders.index'))
            ->assertOk();

        $this->actingAs($staff)
            ->post(route('admin.orders.archive', $order))
            ->assertForbidden();

        $this->actingAs($staff)
            ->delete(route('admin.orders.destroy', $order))
            ->assertForbidden();

        $this->assertNull($order->fresh()->archived_at);
        $this->assertNull($order->fresh()->deleted_at);
    }

    public function test_guest_is_redirected_from_the_orders_page(): void
    {
        $this->get(route('admin.orders.index'))
            ->assertRedirect(route('login'));
    }

    private function admin(): User
    {
        Role::findOrCreate('SUPER_ADMIN', 'web');

        $admin = User::factory()->create([
            'role' => 'SUPER_ADMIN',
            'is_active' => true,
        ]);
        $admin->assignRole('SUPER_ADMIN');

        return $admin;
    }

    private function staffWithViewOnly(): User
    {
        $role = Role::findOrCreate('PLATFORM_STAFF', 'web');
        Permission::findOrCreate('orders.view', 'web');
        $role->syncPermissions(['orders.view']);

        $staff = User::factory()->create([
            'role' => 'PLATFORM_STAFF',
            'is_active' => true,
        ]);
        $staff->assignRole('PLATFORM_STAFF');

        return $staff;
    }

    private function restaurant(): Restaurant
    {
        return Restaurant::query()->create([
            'name' => 'مطعم الطلبات',
            'slug' => 'orders-restaurant-'.uniqid(),
            'address' => 'برج العرب',
            'status' => 'ACTIVE',
            'availability_status' => 'OPEN',
            'delivery_provider' => 'PLATFORM',
        ]);
    }

    private function order(Restaurant $restaurant, string $number): Order
    {
        $customerUser = User::factory()->create([
            'role' => 'CUSTOMER',
            'is_active' => true,
        ]);
        $customer = Customer::query()->create([
            'user_id' => $customerUser->id,
            'student_status' => 'NONE',
        ]);

        return Order::query()->create([
            'order_number' => $number,
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'DELIVERED',
            'payment_status' => 'PENDING',
            'payment_method' => 'CASH_ON_DELIVERY',
            'subtotal' => 40,
            'total_amount' => 55,
            'address' => 'برج العرب',
        ]);
    }
}
