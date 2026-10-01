<?php

namespace Tests\Feature\Admin;

use App\Models\Invoice;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\FinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RestaurantStoreTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        Role::findOrCreate('SUPER_ADMIN', 'web');
        Role::findOrCreate('RESTAURANT_OWNER', 'web');

        $admin = User::factory()->create([
            'role' => 'SUPER_ADMIN',
            'is_active' => true,
        ]);

        $admin->assignRole('SUPER_ADMIN');

        return $admin;
    }

    public function test_admin_can_create_restaurant_with_owner_account(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post('/admin/restaurants', [
            'name' => 'مطعم الاختبار',
            'description' => 'وصف تجريبي',
            'phone' => '01000000001',
            'email' => 'test-restaurant@example.com',
            'address' => 'برج العرب',
            'opening_time' => '08:00',
            'closing_time' => '23:00',
            'delivery_fee' => '12.50',
            'delivery_provider' => 'RESTAURANT',
            'minimum_order_amount' => '25.00',
            'estimated_delivery_time' => 35,
            'commission_rate' => 12,
            'owner_name' => 'مالك الاختبار',
            'owner_email' => 'owner-test@example.com',
            'owner_password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.restaurants.index'));

        $this->assertDatabaseHas('restaurants', [
            'name' => 'مطعم الاختبار',
            'phone' => '01000000001',
            'status' => 'ACTIVE',
            'availability_status' => 'CLOSED',
            'delivery_provider' => 'RESTAURANT',
            'commission_percentage' => 12,
            'commission_type' => 'PERCENTAGE',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'owner-test@example.com',
            'role' => 'RESTAURANT_OWNER',
        ]);

        $restaurant = Restaurant::where('name', 'مطعم الاختبار')->first();
        $owner = User::where('email', 'owner-test@example.com')->first();

        $this->assertNotNull($restaurant);
        $this->assertNotNull($owner);
        $this->assertDatabaseHas('restaurant_staff', [
            'restaurant_id' => $restaurant->id,
            'user_id' => $owner->id,
            'role' => 'OWNER',
        ]);
    }

    public function test_create_restaurant_requires_owner_fields(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->from('/admin/restaurants/create')->post('/admin/restaurants', [
            'name' => 'مطعم ناقص',
            'phone' => '01000000002',
            'address' => 'برج العرب',
        ]);

        $response->assertRedirect('/admin/restaurants/create');
        $response->assertSessionHasErrors(['owner_name', 'owner_email', 'owner_password']);
        $this->assertDatabaseMissing('restaurants', ['name' => 'مطعم ناقص']);
    }

    public function test_paid_subscription_is_counted_in_profits_and_stays_active_through_grace(): void
    {
        Carbon::setTestNow('2026-10-02 12:00:00');
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post('/admin/restaurants', $this->restaurantPayload([
            'name' => 'مطعم الاشتراك المدفوع',
            'phone' => '01000000031',
            'owner_email' => 'paid-sub-owner@example.com',
            'subscription_plan' => 'YEARLY',
            'subscription_amount' => 1200,
            'subscription_paid' => true,
            'grace_period_days' => 5,
            'payment_method' => 'CASH',
        ]));

        $response->assertRedirect(route('admin.restaurants.index'));

        $restaurant = Restaurant::where('name', 'مطعم الاشتراك المدفوع')->first();
        $this->assertNotNull($restaurant);
        $this->assertSame('YEARLY', $restaurant->billing_cycle);
        $this->assertSame(5, $restaurant->grace_period_days);
        $this->assertSame('2026-10-02', $restaurant->subscription_starts_at->toDateString());
        $this->assertSame('2027-10-02', $restaurant->subscription_ends_at->toDateString());
        $this->assertSame('2027-10-07', $restaurant->payment_due_date->toDateString());

        $this->assertDatabaseHas('invoices', [
            'restaurant_id' => $restaurant->id,
            'invoice_type' => 'SUBSCRIPTION',
            'status' => 'PAID',
            'total_amount' => 1200,
            'paid_amount' => 1200,
        ]);
        $this->assertDatabaseHas('collections', [
            'restaurant_id' => $restaurant->id,
            'amount' => 1200,
            'payment_method' => 'CASH',
        ]);

        $summary = app(FinancialService::class)->getPlatformSummary('2026-10-01', '2026-10-31');
        $this->assertSame(1200.0, $summary['subscription_revenue']);
        $this->assertSame(1200.0, $summary['total_revenue']);

        $owner = User::where('email', 'paid-sub-owner@example.com')->first();
        $this->assertNotNull($owner);

        Carbon::setTestNow('2027-10-07 12:00:00');
        $this->actingAs($owner)->get('/restaurant/dashboard');
        $this->assertSame('ACTIVE', $restaurant->fresh()->status);

        Carbon::setTestNow('2027-10-08 12:00:00');
        $this->actingAs($owner)->get('/restaurant/dashboard')->assertRedirectToRoute('restaurant.billing');
        $this->assertSame('SUSPENDED', $restaurant->fresh()->status);

        Carbon::setTestNow('2026-11-02 12:00:00');
        $restaurant->update(['status' => 'ACTIVE', 'billing_suspended_at' => null, 'suspension_reason' => null]);
        $this->actingAs($admin)->post('/admin/billing/auto-generate')->assertRedirect();
        $this->assertSame(1, Invoice::where('restaurant_id', $restaurant->id)->count());

        Carbon::setTestNow();
    }

    public function test_unpaid_subscription_sets_grace_without_recording_profit(): void
    {
        Carbon::setTestNow('2026-10-02 12:00:00');
        $admin = $this->adminUser();

        $this->actingAs($admin)->post('/admin/restaurants', $this->restaurantPayload([
            'name' => 'مطعم اشتراك غير مدفوع',
            'phone' => '01000000032',
            'owner_email' => 'unpaid-sub-owner@example.com',
            'subscription_plan' => 'QUARTERLY',
            'subscription_amount' => 800,
            'subscription_paid' => false,
            'grace_period_days' => 3,
        ]))->assertRedirect(route('admin.restaurants.index'));

        $restaurant = Restaurant::where('name', 'مطعم اشتراك غير مدفوع')->first();
        $this->assertNotNull($restaurant);
        $this->assertSame('2027-01-02', $restaurant->subscription_ends_at->toDateString());
        $this->assertSame('2027-01-05', $restaurant->payment_due_date->toDateString());

        $this->assertDatabaseHas('invoices', [
            'restaurant_id' => $restaurant->id,
            'invoice_type' => 'SUBSCRIPTION',
            'status' => 'ISSUED',
            'total_amount' => 800,
            'paid_amount' => 0,
        ]);
        $this->assertDatabaseMissing('collections', [
            'restaurant_id' => $restaurant->id,
        ]);

        $summary = app(FinancialService::class)->getPlatformSummary('2026-10-01', '2026-10-31');
        $this->assertSame(0.0, $summary['subscription_revenue']);
        $this->assertSame(800.0, $summary['outstanding_receivables']);

        Carbon::setTestNow();
    }

    public function test_percentage_billing_stores_the_order_rate_without_a_subscription(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->post('/admin/restaurants', $this->restaurantPayload([
            'name' => 'مطعم النسبة',
            'phone' => '01000000041',
            'owner_email' => 'percent-owner@example.com',
            'billing_model' => 'percentage',
            'commission_rate' => 18,
            'subscription_amount' => 900,
            'subscription_paid' => true,
            'payment_method' => 'CASH',
        ]))->assertRedirect(route('admin.restaurants.index'));

        $this->assertDatabaseHas('restaurants', [
            'name' => 'مطعم النسبة',
            'commission_type' => 'PERCENTAGE',
            'commission_percentage' => 18,
            'monthly_subscription_fee' => 0,
            'subscription_ends_at' => null,
        ]);
        $this->assertDatabaseMissing('invoices', [
            'invoice_type' => 'SUBSCRIPTION',
        ]);
    }

    public function test_subscription_billing_does_not_keep_an_order_percentage(): void
    {
        Carbon::setTestNow('2026-10-02 12:00:00');
        $admin = $this->adminUser();

        $this->actingAs($admin)->post('/admin/restaurants', $this->restaurantPayload([
            'name' => 'مطعم اشتراك بدون نسبة',
            'phone' => '01000000042',
            'owner_email' => 'sub-only-owner@example.com',
            'billing_model' => 'subscription',
            'commission_rate' => 20,
            'subscription_plan' => 'MONTHLY',
            'subscription_amount' => 400,
            'subscription_paid' => true,
            'grace_period_days' => 7,
            'payment_method' => 'CASH',
        ]))->assertRedirect(route('admin.restaurants.index'));

        $this->assertDatabaseHas('restaurants', [
            'name' => 'مطعم اشتراك بدون نسبة',
            'commission_type' => 'SUBSCRIPTION',
            'commission_percentage' => 0,
            'monthly_subscription_fee' => 400,
            'billing_cycle' => 'MONTHLY',
        ]);
        $this->assertDatabaseHas('invoices', [
            'invoice_type' => 'SUBSCRIPTION',
            'status' => 'PAID',
            'total_amount' => 400,
        ]);

        Carbon::setTestNow();
    }

    public function test_percentage_billing_requires_a_rate(): void
    {
        $admin = $this->adminUser();
        $payload = $this->restaurantPayload([
            'name' => 'مطعم بدون نسبة',
            'phone' => '01000000043',
            'owner_email' => 'missing-rate-owner@example.com',
            'billing_model' => 'percentage',
        ]);
        unset($payload['commission_rate']);

        $this->actingAs($admin)
            ->from('/admin/restaurants/create')
            ->post('/admin/restaurants', $payload)
            ->assertRedirect('/admin/restaurants/create')
            ->assertSessionHasErrors('commission_rate');

        $this->assertDatabaseMissing('restaurants', ['name' => 'مطعم بدون نسبة']);
    }

    public function test_marking_subscription_paid_requires_an_amount(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->from('/admin/restaurants/create')
            ->post('/admin/restaurants', $this->restaurantPayload([
                'subscription_paid' => true,
                'subscription_amount' => 0,
                'payment_method' => 'CASH',
            ]))
            ->assertRedirect('/admin/restaurants/create')
            ->assertSessionHasErrors('subscription_amount');

        $this->assertDatabaseMissing('invoices', [
            'invoice_type' => 'SUBSCRIPTION',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function restaurantPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'مطعم الاختبار',
            'phone' => '01000000001',
            'address' => 'برج العرب',
            'delivery_provider' => 'RESTAURANT',
            'commission_rate' => 12,
            'owner_name' => 'مالك الاختبار',
            'owner_email' => 'owner-test@example.com',
            'owner_password' => 'password123',
        ], $overrides);
    }
}
