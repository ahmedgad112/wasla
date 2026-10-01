<?php

namespace Tests\Feature\Restaurant;

use App\Models\Category;
use App\Models\Customer;
use App\Models\DeliveryDriver;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\RestaurantStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_driver_can_be_assigned_only_after_the_order_is_ready(): void
    {
        $owner = $this->owner();
        $restaurant = $owner->restaurant;
        $driver = $this->driver($restaurant);
        $pending = $this->order($restaurant, 'PENDING');
        $ready = $this->order($restaurant, 'READY_FOR_PICKUP');

        $this->actingAs($owner)
            ->post("/restaurant/orders/{$pending->id}/assign-driver", [
                'driver_id' => $driver->id,
            ])
            ->assertSessionHasErrors('driver');

        $this->assertSame('PENDING', $pending->fresh()->status);
        $this->assertNull($pending->fresh()->assigned_delivery_id);

        $this->actingAs($owner)
            ->from('/restaurant/orders')
            ->post("/restaurant/orders/{$ready->id}/assign-driver", [
                'driver_id' => $driver->id,
            ])
            ->assertRedirect('/restaurant/orders')
            ->assertSessionHas('success');

        $this->assertSame('ASSIGNED_TO_DRIVER', $ready->fresh()->status);
        $this->assertSame($driver->id, $ready->fresh()->assigned_delivery_id);
    }

    public function test_suspended_staff_can_open_the_billing_notice(): void
    {
        $staff = $this->owner('RESTAURANT_STAFF');
        $staff->restaurant->update(['status' => 'SUSPENDED']);

        $this->actingAs($staff)
            ->get('/restaurant/billing')
            ->assertOk();
    }

    public function test_a_menu_item_cannot_move_into_another_restaurants_category(): void
    {
        $owner = $this->owner();
        $restaurant = $owner->restaurant;
        $category = Category::query()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'تصنيف المطعم',
            'slug' => 'own-category',
        ]);
        $item = MenuItem::query()->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'name' => 'طبق',
            'price' => 25,
            'is_available' => true,
        ]);
        $other = Restaurant::query()->create([
            'name' => 'مطعم آخر',
            'slug' => 'other-menu-restaurant',
            'address' => 'برج العرب',
            'status' => 'ACTIVE',
            'availability_status' => 'OPEN',
        ]);
        $foreignCategory = Category::query()->create([
            'restaurant_id' => $other->id,
            'name' => 'تصنيف غريب',
            'slug' => 'foreign-category',
        ]);

        $this->actingAs($owner)
            ->put("/restaurant/menu/{$item->id}", [
                'category_id' => $foreignCategory->id,
                'name' => 'طبق',
                'price' => 25,
            ])
            ->assertNotFound();

        $this->assertSame($category->id, $item->fresh()->category_id);
    }

    private function owner(string $role = 'RESTAURANT_OWNER'): User
    {
        Role::findOrCreate($role, 'web');

        $user = User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);
        $user->assignRole($role);

        $restaurant = Restaurant::query()->create([
            'name' => 'مطعم الإسناد',
            'slug' => 'assign-restaurant-'.$role,
            'address' => 'برج العرب',
            'status' => 'ACTIVE',
            'availability_status' => 'OPEN',
            'delivery_provider' => 'PLATFORM',
        ]);

        RestaurantStaff::query()->create([
            'restaurant_id' => $restaurant->id,
            'user_id' => $user->id,
            'role' => $role,
            'is_active' => true,
        ]);

        return $user;
    }

    private function driver(Restaurant $restaurant): DeliveryDriver
    {
        Role::findOrCreate('DELIVERY_DRIVER', 'web');

        $user = User::factory()->create([
            'role' => 'DELIVERY_DRIVER',
            'is_active' => true,
        ]);
        $user->assignRole('DELIVERY_DRIVER');

        return DeliveryDriver::query()->create([
            'user_id' => $user->id,
            'restaurant_id' => $restaurant->id,
            'name' => 'كابتن',
            'phone' => '01022223333',
            'is_active' => true,
            'availability_status' => 'AVAILABLE',
        ]);
    }

    private function order(Restaurant $restaurant, string $status): Order
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
            'order_number' => 'FS-TEST-'.$status.'-'.$customer->id,
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => $status,
            'payment_status' => 'PENDING',
            'payment_method' => 'CASH_ON_DELIVERY',
            'subtotal' => 40,
            'total_amount' => 55,
            'address' => 'برج العرب',
        ]);
    }
}
