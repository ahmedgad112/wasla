<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportsPageTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guest_is_redirected_from_reports(): void
    {
        $this->get('/admin/reports')
            ->assertRedirect(route('login'));
    }

    public function test_staff_without_analytics_permission_cannot_open_reports(): void
    {
        Role::findOrCreate('PLATFORM_STAFF', 'web');

        $staff = User::factory()->create([
            'role' => 'PLATFORM_STAFF',
            'is_active' => true,
        ]);
        $staff->assignRole('PLATFORM_STAFF');

        $this->actingAs($staff)
            ->get('/admin/reports')
            ->assertForbidden();
    }

    public function test_reports_split_platform_money_orders_and_restaurants_for_the_selected_period(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-05 15:00:00'));

        $admin = $this->adminUser();
        $restaurant = $this->restaurant();
        $customer = $this->customer();

        $this->deliveredOrder($restaurant, $customer, [
            'order_number' => 'FS-REPORT-TODAY',
            'subtotal' => 80,
            'student_discount_amount' => 5,
            'delivery_fee' => 15,
            'total_amount' => 90,
            'platform_commission_amount' => 8,
            'payment_method' => 'CASH_ON_DELIVERY',
            'created_at' => now()->subMinutes(20),
            'delivered_at' => now(),
        ], 'كشري', 2, 80);

        Order::query()->create([
            'order_number' => 'FS-REPORT-CANCELLED',
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'CANCELLED',
            'payment_status' => 'PENDING',
            'payment_method' => 'CASH_ON_DELIVERY',
            'subtotal' => 40,
            'total_amount' => 40,
            'address' => 'برج العرب',
        ]);

        $this->deliveredOrder($restaurant, $customer, [
            'order_number' => 'FS-REPORT-YESTERDAY',
            'subtotal' => 200,
            'delivery_fee' => 0,
            'total_amount' => 200,
            'platform_commission_amount' => 20,
            'payment_method' => 'WALLET',
            'created_at' => now()->subDay(),
            'delivered_at' => now()->subDay()->addHour(),
        ], 'فراخ', 1, 200);

        Invoice::query()->create([
            'invoice_number' => 'INV-REPORT-SUB',
            'restaurant_id' => $restaurant->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'subtotal' => 500,
            'total_amount' => 500,
            'paid_amount' => 500,
            'status' => 'PAID',
            'invoice_type' => 'SUBSCRIPTION',
        ]);

        Invoice::query()->create([
            'invoice_number' => 'INV-REPORT-DUE',
            'restaurant_id' => $restaurant->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'subtotal' => 30,
            'total_amount' => 30,
            'paid_amount' => 0,
            'status' => 'ISSUED',
            'invoice_type' => 'COMMISSION',
        ]);

        $category = ExpenseCategory::query()->create([
            'name' => 'تسويق',
        ]);
        Expense::query()->create([
            'expense_category_id' => $category->id,
            'amount' => 100,
            'description' => 'إعلان',
            'expense_date' => now()->toDateString(),
            'created_by_user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get('/admin/reports?period=month')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Reports/Index')
                ->where('period.key', 'month')
                ->where('platform.subscription_collected', 500)
                ->where('platform.expenses', 100)
                ->where('platform.net_profit', 400)
                ->where('platform.outstanding', 30)
                ->where('orders.total', 3)
                ->where('orders.buckets.3.key', 'delivered')
                ->where('orders.buckets.3.count', 2)
                ->where('orders.buckets.4.count', 1)
                ->where('delivered_money.customer_paid', 290)
                ->where('delivered_money.delivery_fees', 15)
                ->where('delivered_money.platform_commission', 28)
                ->where('delivered_money.restaurant_net', 247)
                ->where('delivered_money.student_discounts', 5)
                ->where('payments.0.method', 'WALLET')
                ->where('restaurants.0.name', 'مطعم التقارير')
                ->where('restaurants.0.unpaid_due', 30)
                ->where('customers.ordered', 1)
                ->where('customers.new', 1)
                ->where('top_items.0.name', 'كشري')
                ->where('top_items.0.qty', 2)
                ->has('daily', 5));

        $this->actingAs($admin)
            ->get('/admin/reports?period=today')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('period.key', 'today')
                ->where('orders.total', 2)
                ->where('delivered_money.customer_paid', 90)
                ->where('delivered_money.restaurant_net', 67)
                ->where('payments.0.method', 'CASH_ON_DELIVERY')
                ->has('daily', 1));
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
            'name' => 'مطعم التقارير',
            'slug' => 'reports-restaurant',
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

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function deliveredOrder(Restaurant $restaurant, Customer $customer, array $attributes, string $itemName, int $quantity, float $itemTotal): void
    {
        $createdAt = $attributes['created_at'] ?? now();
        unset($attributes['created_at']);

        $order = Order::query()->create(array_merge([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'DELIVERED',
            'payment_status' => 'PAID',
            'address' => 'برج العرب',
        ], $attributes));
        $order->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        OrderItem::query()->create([
            'order_id' => $order->id,
            'name' => $itemName,
            'unit_price' => $itemTotal / $quantity,
            'quantity' => $quantity,
            'total_price' => $itemTotal,
        ]);
    }
}
