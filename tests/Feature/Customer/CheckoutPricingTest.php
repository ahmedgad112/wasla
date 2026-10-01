<?php

namespace Tests\Feature\Customer;

use App\Models\Category;
use App\Models\Customer;
use App\Models\MenuItem;
use App\Models\MenuItemAddon;
use App\Models\MenuItemOption;
use App\Models\MenuItemOptionValue;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CheckoutPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_charges_database_prices_and_ignores_the_client_delivery_fee(): void
    {
        [$user] = $this->customer();
        $restaurant = $this->restaurant();
        $item = $this->menuItem($restaurant, 40);
        $value = $this->optionValue($item, 12);

        $this->actingAs($user)
            ->post('/checkout', [
                'restaurant_id' => $restaurant->id,
                'items' => [[
                    'menu_item_id' => $item->id,
                    'quantity' => 1,
                    'options' => [[
                        'optionName' => 'الحجم',
                        'valueName' => 'كبير',
                        'price' => 0,
                    ]],
                ]],
                'address' => 'برج العرب',
                'delivery_fee' => 1,
                'payment_method' => 'CASH_ON_DELIVERY',
            ])
            ->assertRedirect();

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertSame('52.00', $order->subtotal);
        $this->assertSame('15.00', $order->delivery_fee);
        $this->assertSame('67.00', $order->total_amount);
        $this->assertEquals(12, $order->items()->first()->selected_options[0]['price']);
        $this->assertNotNull($value->id);
    }

    public function test_checkout_rejects_an_option_or_addon_from_another_dish(): void
    {
        [$user] = $this->customer();
        $restaurant = $this->restaurant();
        $item = $this->menuItem($restaurant, 40);
        $other = $this->menuItem($restaurant, 30, 'صنف آخر');
        $foreignOption = $this->optionValue($other, 9);
        $foreignAddon = MenuItemAddon::query()->create([
            'menu_item_id' => $other->id,
            'name' => 'إضافة غريبة',
            'price' => 5,
            'is_available' => true,
        ]);

        $this->actingAs($user)
            ->post('/checkout', [
                'restaurant_id' => $restaurant->id,
                'items' => [[
                    'menu_item_id' => $item->id,
                    'quantity' => 1,
                    'options' => [$foreignOption->id],
                ]],
                'address' => 'برج العرب',
                'payment_method' => 'CASH_ON_DELIVERY',
            ])
            ->assertSessionHasErrors('items');

        $this->actingAs($user)
            ->post('/checkout', [
                'restaurant_id' => $restaurant->id,
                'items' => [[
                    'menu_item_id' => $item->id,
                    'quantity' => 1,
                    'addons' => [['id' => $foreignAddon->id, 'price' => 0]],
                ]],
                'address' => 'برج العرب',
                'payment_method' => 'CASH_ON_DELIVERY',
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_deleting_an_address_without_a_customer_profile_is_forbidden(): void
    {
        [$user] = $this->customer(createProfile: false);

        $this->actingAs($user)
            ->delete('/profile/addresses/1')
            ->assertForbidden();
    }

    public function test_a_user_cannot_have_two_customer_profiles(): void
    {
        [$user] = $this->customer();

        $this->expectException(QueryException::class);

        Customer::query()->create([
            'user_id' => $user->id,
            'student_status' => 'NONE',
        ]);
    }

    /**
     * @return array{0: User, 1: Customer|null}
     */
    private function customer(bool $createProfile = true): array
    {
        Role::findOrCreate('CUSTOMER', 'web');

        $user = User::factory()->create([
            'role' => 'CUSTOMER',
            'is_active' => true,
        ]);
        $user->assignRole('CUSTOMER');

        $customer = $createProfile
            ? Customer::query()->create([
                'user_id' => $user->id,
                'student_status' => 'NONE',
            ])
            : null;

        return [$user, $customer];
    }

    private function restaurant(): Restaurant
    {
        return Restaurant::query()->create([
            'name' => 'مطعم التسعير',
            'slug' => 'pricing-restaurant',
            'phone' => '01000000001',
            'address' => 'برج العرب',
            'status' => 'ACTIVE',
            'availability_status' => 'OPEN',
            'delivery_provider' => 'PLATFORM',
            'delivery_fee' => 15,
            'delivery_fee_per_km' => 0,
            'minimum_order_amount' => 10,
            'commission_type' => 'PERCENTAGE',
            'commission_percentage' => 10,
            'student_discount_percentage' => 0,
        ]);
    }

    private function menuItem(Restaurant $restaurant, float $price, string $name = 'ساندويتش'): MenuItem
    {
        $category = Category::query()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'تصنيف '.$name,
            'slug' => 'cat-'.md5($name),
        ]);

        return MenuItem::query()->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'name' => $name,
            'price' => $price,
            'is_available' => true,
        ]);
    }

    private function optionValue(MenuItem $item, float $price): MenuItemOptionValue
    {
        $option = MenuItemOption::query()->create([
            'menu_item_id' => $item->id,
            'name' => 'الحجم',
            'is_required' => false,
        ]);

        return MenuItemOptionValue::query()->create([
            'menu_item_option_id' => $option->id,
            'name' => 'كبير',
            'price' => $price,
        ]);
    }
}
