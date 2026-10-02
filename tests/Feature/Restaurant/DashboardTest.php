<?php

namespace Tests\Feature\Restaurant;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\RestaurantStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_restaurant_dashboard(): void
    {
        $this->get('/restaurant/dashboard')
            ->assertRedirect(route('login'));
    }

    public function test_dashboard_lists_this_restaurants_delivered_items_from_the_last_30_days(): void
    {
        $owner = $this->owner();
        $restaurant = $owner->restaurant;
        $customer = $this->customer('عميل الطلب');

        $this->addItem(
            $this->order($restaurant, $customer, 'DELIVERED', 'FS-TOP-FUL', now()->subHour()),
            'طبق فول',
            2,
            40,
        );
        $this->addItem(
            $this->order($restaurant, $customer, 'DELIVERED', 'FS-TOP-FUL-2', now()),
            'طبق فول',
            1,
            20,
        );
        $this->addItem(
            $this->order($restaurant, $customer, 'DELIVERED', 'FS-TOP-SAND', now()->subHours(2)),
            'ساندوتش',
            1,
            15,
        );
        $this->addItem(
            $this->order($restaurant, $customer, 'CANCELLED', 'FS-TOP-CANCELLED', now()->subMinutes(10)),
            'ملغي',
            9,
            90,
        );
        $this->addItem(
            $this->order($restaurant, $customer, 'DELIVERED', 'FS-TOP-OLD', now()->subDays(40)),
            'قديم',
            8,
            80,
        );
        $this->addItem(
            $this->order($this->otherRestaurant(), $this->customer('عميل آخر'), 'DELIVERED', 'FS-TOP-OTHER', now()),
            'طبق غريب',
            7,
            70,
        );

        $this->actingAs($owner)
            ->get('/restaurant/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Restaurant/Dashboard')
                ->has('top_items', 2)
                ->where('top_items.0.name', 'طبق فول')
                ->where('top_items.0.total_qty', 3)
                ->where('top_items.0.total_revenue', 60)
                ->where('top_items.1.name', 'ساندوتش')
                ->where('top_items.1.total_qty', 1)
                ->where('recent_orders.0.order_number', 'FS-TOP-FUL-2')
                ->where('recent_orders.0.customer.user.name', 'عميل الطلب'));

        $restored = unserialize(
            serialize(Cache::get('dashboard.restaurant.'.$restaurant->id)),
            ['allowed_classes' => false],
        );

        $this->assertSame('طبق فول', $restored['top_items'][0]['name']);
        $this->assertSame(3, $restored['top_items'][0]['total_qty']);
        $this->assertSame(60.0, $restored['top_items'][0]['total_revenue']);
        $this->assertSame('FS-TOP-FUL-2', $restored['recent_orders'][0]['order_number']);

        $this->actingAs($owner)
            ->get('/restaurant/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('top_items.0.name', 'طبق فول')
                ->where('top_items.0.total_qty', 3));
    }

    private function owner(): User
    {
        Role::findOrCreate('RESTAURANT_OWNER', 'web');

        $user = User::factory()->create([
            'role' => 'RESTAURANT_OWNER',
            'is_active' => true,
        ]);
        $user->assignRole('RESTAURANT_OWNER');

        $restaurant = Restaurant::query()->create([
            'name' => 'مطعم اللوحة',
            'slug' => 'dashboard-restaurant',
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

        RestaurantStaff::query()->create([
            'restaurant_id' => $restaurant->id,
            'user_id' => $user->id,
            'role' => 'OWNER',
            'is_active' => true,
        ]);

        return $user->fresh();
    }

    private function otherRestaurant(): Restaurant
    {
        return Restaurant::query()->create([
            'name' => 'مطعم آخر',
            'slug' => 'other-dashboard-restaurant',
            'phone' => '01001112234',
            'address' => 'برج العرب',
            'status' => 'ACTIVE',
            'availability_status' => 'OPEN',
            'minimum_order_amount' => 0,
            'delivery_fee' => 0,
            'commission_type' => 'PERCENTAGE',
            'commission_percentage' => 0,
            'monthly_subscription_fee' => 0,
            'student_discount_percentage' => 0,
        ]);
    }

    private function customer(string $name): Customer
    {
        $user = User::factory()->create([
            'name' => $name,
            'role' => 'CUSTOMER',
            'is_active' => true,
        ]);

        return Customer::query()->create([
            'user_id' => $user->id,
            'student_status' => 'NONE',
        ]);
    }

    private function order(Restaurant $restaurant, Customer $customer, string $status, string $number, mixed $createdAt): Order
    {
        $order = Order::query()->create([
            'order_number' => $number,
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => $status,
            'payment_status' => 'PAID',
            'payment_method' => 'CASH_ON_DELIVERY',
            'subtotal' => 40,
            'total_amount' => 55,
            'address' => 'برج العرب',
        ]);
        $order->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $order;
    }

    private function addItem(Order $order, string $name, int $quantity, float $totalPrice): void
    {
        OrderItem::query()->create([
            'order_id' => $order->id,
            'name' => $name,
            'unit_price' => $totalPrice / $quantity,
            'quantity' => $quantity,
            'total_price' => $totalPrice,
        ]);
    }
}
