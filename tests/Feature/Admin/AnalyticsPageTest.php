<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AnalyticsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guest_is_redirected_from_admin_analytics(): void
    {
        $this->get('/admin/analytics')
            ->assertRedirect(route('login'));
    }

    public function test_admin_analytics_page_loads_recent_orders(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 15:10:00'));

        $admin = $this->adminUser();
        $restaurant = $this->restaurant();
        $customer = $this->customer();
        $createdAt = now()->subMinutes(30);

        $order = Order::query()->create([
            'order_number' => 'FS-ANALYTICS-1',
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'DELIVERED',
            'payment_status' => 'PAID',
            'payment_method' => 'CASH_ON_DELIVERY',
            'subtotal' => 40,
            'total_amount' => 55,
            'address' => 'برج العرب',
            'delivered_at' => now(),
        ]);
        $order->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        OrderItem::query()->create([
            'order_id' => $order->id,
            'name' => 'كشري',
            'unit_price' => 40,
            'quantity' => 2,
            'total_price' => 80,
        ]);

        $this->actingAs($admin)
            ->get('/admin/analytics')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Analytics/Index')
                ->where('summaryStats.total_orders_30d', 1)
                ->where('summaryStats.revenue_30d', 55)
                ->where('avgDeliveryTime', 30)
                ->where('customerRetention.new_customers', 1)
                ->where('topRestaurants.0.name', 'مطعم المؤشرات')
                ->where('topRestaurants.0.orders', 1)
                ->where('topMenuItems.0.name', 'كشري')
                ->where('topMenuItems.0.count', 2)
                ->has('ordersByHour', 24)
                ->has('ordersByDay', 30)
                ->where('ordersByHour.14.count', 1));
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

    private function restaurant(): Restaurant
    {
        return Restaurant::query()->create([
            'name' => 'مطعم المؤشرات',
            'slug' => 'analytics-restaurant',
            'phone' => '01001112233',
            'address' => 'برج العرب',
            'status' => 'ACTIVE',
            'availability_status' => 'OPEN',
            'opening_time' => '08:00:00',
            'closing_time' => '23:00:00',
            'minimum_order_amount' => 20,
            'delivery_fee' => 10,
            'commission_type' => 'PERCENTAGE',
            'commission_percentage' => 10,
            'monthly_subscription_fee' => 0,
            'student_discount_percentage' => 0,
        ]);
    }

    private function customer(): Customer
    {
        $user = User::factory()->create([
            'role' => 'CUSTOMER',
            'is_active' => true,
        ]);

        return Customer::query()->create([
            'user_id' => $user->id,
            'student_status' => 'NONE',
        ]);
    }
}
