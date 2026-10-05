<?php

namespace Tests\Feature\Admin;

use App\Models\Collection;
use App\Models\Invoice;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\FinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubscriptionRenewalTest extends TestCase
{
    use RefreshDatabase;

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

    private function restaurant(array $overrides = []): Restaurant
    {
        return Restaurant::query()->create(array_merge([
            'name' => 'مطعم التجديد',
            'slug' => 'renew-restaurant',
            'phone' => '01005556677',
            'address' => 'برج العرب',
            'status' => 'ACTIVE',
            'availability_status' => 'CLOSED',
            'opening_time' => '08:00:00',
            'closing_time' => '23:00:00',
            'commission_type' => 'PERCENTAGE',
            'commission_percentage' => 10,
            'monthly_subscription_fee' => 500,
            'billing_cycle' => 'MONTHLY',
            'grace_period_days' => 5,
            'subscription_starts_at' => '2026-11-02',
            'subscription_ends_at' => '2026-12-02',
            'payment_due_date' => '2026-12-07',
        ], $overrides));
    }

    public function test_admin_can_open_restaurant_statement_and_renew_at_the_same_price(): void
    {
        Carbon::setTestNow('2026-10-02 12:00:00');
        $admin = $this->admin();
        $restaurant = $this->restaurant();

        $this->actingAs($admin)
            ->get("/admin/finance/restaurants/{$restaurant->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Finance/RestaurantStatement')
                ->where('renewal.starts_at', '2026-12-02')
                ->where('renewal.ends_at', '2027-01-02')
                ->where('dues.commission', 0)
            );

        $this->actingAs($admin)
            ->post("/admin/finance/restaurants/{$restaurant->id}/renew-subscription", [
                'price_mode' => 'same',
                'payment_method' => 'CASH',
            ])
            ->assertRedirect(route('admin.finance.restaurants.show', $restaurant->id));

        $restaurant->refresh();
        $this->assertSame('SUBSCRIPTION', $restaurant->commission_type);
        $this->assertSame(0.0, (float) $restaurant->commission_percentage);
        $this->assertSame(500.0, (float) $restaurant->monthly_subscription_fee);
        $this->assertSame('2026-12-02', $restaurant->subscription_starts_at->toDateString());
        $this->assertSame('2027-01-02', $restaurant->subscription_ends_at->toDateString());
        $this->assertSame('2027-01-07', $restaurant->payment_due_date->toDateString());

        $this->assertDatabaseHas('invoices', [
            'restaurant_id' => $restaurant->id,
            'invoice_type' => 'SUBSCRIPTION',
            'status' => 'PAID',
            'total_amount' => 500,
        ]);
        $this->assertDatabaseHas('collections', [
            'restaurant_id' => $restaurant->id,
            'amount' => 500,
            'payment_method' => 'CASH',
        ]);

        $summary = app(FinancialService::class)->getPlatformSummary('2026-10-01', '2026-10-31');
        $this->assertSame(500.0, $summary['subscription_revenue']);

        Carbon::setTestNow();
    }

    public function test_custom_price_renewal_is_collected_while_unpaid_commission_keeps_the_account_suspended(): void
    {
        Carbon::setTestNow('2026-10-02 12:00:00');
        $admin = $this->admin();
        $restaurant = $this->restaurant([
            'slug' => 'lapsed-restaurant',
            'status' => 'SUSPENDED',
            'billing_suspended_at' => '2026-09-02 10:00:00',
            'suspension_reason' => 'تجاوز موعد الاستحقاق دون تسجيل السداد',
            'subscription_ends_at' => '2026-09-02',
            'payment_due_date' => '2026-09-07',
            'monthly_subscription_fee' => 400,
        ]);

        Invoice::query()->create([
            'invoice_number' => 'INV-OLD-1',
            'restaurant_id' => $restaurant->id,
            'issue_date' => '2026-08-02',
            'due_date' => '2026-09-07',
            'subtotal' => 80,
            'tax_amount' => 0,
            'total_amount' => 80,
            'paid_amount' => 0,
            'status' => 'ISSUED',
            'invoice_type' => 'COMMISSION',
        ]);

        $this->actingAs($admin)
            ->get("/admin/finance/restaurants/{$restaurant->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('dues.commission', 80));

        $this->actingAs($admin)
            ->from("/admin/finance/restaurants/{$restaurant->id}")
            ->post("/admin/finance/restaurants/{$restaurant->id}/renew-subscription", [
                'price_mode' => 'custom',
                'amount' => 750,
                'payment_method' => 'INSTAPAY',
            ])
            ->assertRedirect(route('admin.finance.restaurants.show', $restaurant->id));

        $restaurant->refresh();
        $this->assertSame(750.0, (float) $restaurant->monthly_subscription_fee);
        $this->assertSame('2026-10-02', $restaurant->subscription_starts_at->toDateString());
        $this->assertSame('2026-11-02', $restaurant->subscription_ends_at->toDateString());
        $this->assertSame('SUSPENDED', $restaurant->status);

        $this->assertDatabaseHas('collections', [
            'restaurant_id' => $restaurant->id,
            'amount' => 750,
            'payment_method' => 'INSTAPAY',
        ]);

        Carbon::setTestNow();
    }

    public function test_renewal_can_switch_the_restaurant_to_an_order_percentage(): void
    {
        Carbon::setTestNow('2026-10-02 12:00:00');
        $admin = $this->admin();
        $restaurant = $this->restaurant([
            'slug' => 'switch-percentage',
            'commission_type' => 'SUBSCRIPTION',
            'commission_percentage' => 0,
        ]);

        $this->actingAs($admin)
            ->post("/admin/finance/restaurants/{$restaurant->id}/renew-subscription", [
                'billing_model' => 'percentage',
                'commission_rate' => 12.5,
            ])
            ->assertRedirect(route('admin.finance.restaurants.show', $restaurant->id));

        $restaurant->refresh();
        $this->assertSame('PERCENTAGE', $restaurant->commission_type);
        $this->assertSame(12.5, (float) $restaurant->commission_percentage);
        $this->assertSame(0.0, (float) $restaurant->monthly_subscription_fee);
        $this->assertNull($restaurant->subscription_ends_at);
        $this->assertSame('2026-12-07', $restaurant->payment_due_date->toDateString());

        $this->assertDatabaseMissing('invoices', [
            'restaurant_id' => $restaurant->id,
            'invoice_type' => 'SUBSCRIPTION',
        ]);
        $this->assertDatabaseMissing('collections', [
            'restaurant_id' => $restaurant->id,
        ]);

        Carbon::setTestNow();
    }

    public function test_percentage_switch_requires_a_rate(): void
    {
        $admin = $this->admin();
        $restaurant = $this->restaurant(['slug' => 'percentage-missing-rate']);

        $this->actingAs($admin)
            ->from("/admin/finance/restaurants/{$restaurant->id}")
            ->post("/admin/finance/restaurants/{$restaurant->id}/renew-subscription", [
                'billing_model' => 'percentage',
            ])
            ->assertRedirect("/admin/finance/restaurants/{$restaurant->id}")
            ->assertSessionHasErrors('commission_rate');

        $this->assertSame(500.0, (float) $restaurant->fresh()->monthly_subscription_fee);
    }

    public function test_percentage_switch_keeps_the_account_suspended_when_commission_is_unpaid(): void
    {
        $admin = $this->admin();
        $restaurant = $this->restaurant([
            'slug' => 'percentage-suspended',
            'status' => 'SUSPENDED',
            'billing_suspended_at' => '2026-09-08 10:00:00',
            'subscription_ends_at' => '2026-09-02',
            'payment_due_date' => '2026-09-07',
        ]);

        Invoice::query()->create([
            'invoice_number' => 'INV-COMM-1',
            'restaurant_id' => $restaurant->id,
            'issue_date' => '2026-08-02',
            'due_date' => '2026-09-07',
            'subtotal' => 80,
            'tax_amount' => 0,
            'total_amount' => 80,
            'paid_amount' => 0,
            'status' => 'ISSUED',
            'invoice_type' => 'COMMISSION',
        ]);

        $this->actingAs($admin)
            ->post("/admin/finance/restaurants/{$restaurant->id}/renew-subscription", [
                'billing_model' => 'percentage',
                'commission_rate' => 8,
            ])
            ->assertRedirect(route('admin.finance.restaurants.show', $restaurant->id));

        $restaurant->refresh();
        $this->assertSame('PERCENTAGE', $restaurant->commission_type);
        $this->assertSame(8.0, (float) $restaurant->commission_percentage);
        $this->assertSame('SUSPENDED', $restaurant->status);
        $this->assertDatabaseHas('invoices', [
            'restaurant_id' => $restaurant->id,
            'invoice_type' => 'COMMISSION',
            'status' => 'ISSUED',
            'total_amount' => 80,
        ]);
    }

    public function test_percentage_switch_reactivates_a_restaurant_suspended_only_for_billing(): void
    {
        $admin = $this->admin();
        $restaurant = $this->restaurant([
            'slug' => 'percentage-reactivate',
            'status' => 'SUSPENDED',
            'billing_suspended_at' => '2026-09-08 10:00:00',
            'subscription_ends_at' => '2026-09-02',
            'payment_due_date' => '2026-09-07',
        ]);

        $this->actingAs($admin)
            ->post("/admin/finance/restaurants/{$restaurant->id}/renew-subscription", [
                'billing_model' => 'percentage',
                'commission_rate' => 10,
            ])
            ->assertRedirect(route('admin.finance.restaurants.show', $restaurant->id));

        $this->assertSame('ACTIVE', $restaurant->fresh()->status);
    }

    public function test_same_price_renewal_requires_an_existing_fee(): void
    {
        $admin = $this->admin();
        $restaurant = $this->restaurant([
            'slug' => 'no-fee-restaurant',
            'monthly_subscription_fee' => 0,
        ]);

        $this->actingAs($admin)
            ->from("/admin/finance/restaurants/{$restaurant->id}")
            ->post("/admin/finance/restaurants/{$restaurant->id}/renew-subscription", [
                'price_mode' => 'same',
                'payment_method' => 'CASH',
            ])
            ->assertRedirect("/admin/finance/restaurants/{$restaurant->id}")
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseMissing('invoices', [
            'restaurant_id' => $restaurant->id,
            'invoice_type' => 'SUBSCRIPTION',
        ]);
    }

    public function test_renewal_reactivates_a_restaurant_suspended_only_for_billing(): void
    {
        Carbon::setTestNow('2026-10-02 12:00:00');
        $admin = $this->admin();
        $restaurant = $this->restaurant([
            'slug' => 'reactivate-restaurant',
            'status' => 'SUSPENDED',
            'billing_suspended_at' => '2026-09-08 10:00:00',
            'subscription_ends_at' => '2026-09-02',
            'payment_due_date' => '2026-09-07',
        ]);

        $this->actingAs($admin)
            ->post("/admin/finance/restaurants/{$restaurant->id}/renew-subscription", [
                'price_mode' => 'same',
                'payment_method' => 'BANK_TRANSFER',
            ])
            ->assertRedirect(route('admin.finance.restaurants.show', $restaurant->id));

        $this->assertSame('ACTIVE', $restaurant->fresh()->status);
        Carbon::setTestNow();
    }

    public function test_renewal_can_collect_part_of_the_new_invoice(): void
    {
        Carbon::setTestNow('2026-10-02 12:00:00');
        $admin = $this->admin();
        $restaurant = $this->restaurant(['slug' => 'partial-renewal']);

        $this->actingAs($admin)
            ->post("/admin/finance/restaurants/{$restaurant->id}/renew-subscription", [
                'price_mode' => 'same',
                'collected_amount' => 200,
                'payment_method' => 'CASH',
            ])
            ->assertRedirect(route('admin.finance.restaurants.show', $restaurant->id))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('invoices', [
            'restaurant_id' => $restaurant->id,
            'invoice_type' => 'SUBSCRIPTION',
            'status' => 'PARTIALLY_PAID',
            'total_amount' => 500,
            'paid_amount' => 200,
        ]);
        $this->assertDatabaseHas('collections', [
            'restaurant_id' => $restaurant->id,
            'amount' => 200,
            'payment_method' => 'CASH',
        ]);

        Carbon::setTestNow();
    }

    public function test_renewal_rejects_a_collection_larger_than_the_invoice(): void
    {
        $admin = $this->admin();
        $restaurant = $this->restaurant(['slug' => 'over-renewal']);

        $this->actingAs($admin)
            ->from("/admin/finance/restaurants/{$restaurant->id}")
            ->post("/admin/finance/restaurants/{$restaurant->id}/renew-subscription", [
                'price_mode' => 'same',
                'collected_amount' => 700,
                'payment_method' => 'CASH',
            ])
            ->assertRedirect("/admin/finance/restaurants/{$restaurant->id}")
            ->assertSessionHasErrors('collected_amount');

        $this->assertDatabaseMissing('invoices', [
            'restaurant_id' => $restaurant->id,
            'invoice_type' => 'SUBSCRIPTION',
        ]);
    }

    public function test_admin_can_collect_part_of_an_open_invoice_from_the_statement(): void
    {
        $admin = $this->admin();
        $restaurant = $this->restaurant(['slug' => 'partial-collection']);
        $invoice = $this->openInvoice($restaurant, 500);

        $this->actingAs($admin)
            ->post("/admin/finance/restaurants/{$restaurant->id}/collections", [
                'invoice_id' => $invoice->id,
                'amount' => 200,
                'payment_method' => 'CASH',
                'notes' => 'دفعة أولى',
            ])
            ->assertRedirect(route('admin.finance.restaurants.show', $restaurant->id))
            ->assertSessionHas('success');

        $invoice->refresh();
        $this->assertSame('PARTIALLY_PAID', $invoice->status);
        $this->assertSame(200.0, (float) $invoice->paid_amount);
        $this->assertDatabaseHas('collections', [
            'restaurant_id' => $restaurant->id,
            'invoice_id' => $invoice->id,
            'payment_method' => 'CASH',
            'notes' => 'دفعة أولى',
            'collected_by_user_id' => $admin->id,
        ]);
    }

    public function test_collecting_the_remaining_amount_marks_the_invoice_paid(): void
    {
        $admin = $this->admin();
        $restaurant = $this->restaurant(['slug' => 'full-collection']);
        $invoice = $this->openInvoice($restaurant, 500, 200, 'PARTIALLY_PAID');

        $this->actingAs($admin)
            ->post("/admin/finance/restaurants/{$restaurant->id}/collections", [
                'invoice_id' => $invoice->id,
                'amount' => 300,
                'payment_method' => 'INSTAPAY',
            ])
            ->assertRedirect(route('admin.finance.restaurants.show', $restaurant->id));

        $invoice->refresh();
        $this->assertSame('PAID', $invoice->status);
        $this->assertSame(500.0, (float) $invoice->paid_amount);
        $this->assertSame(1, Collection::query()->where('invoice_id', $invoice->id)->count());
    }

    public function test_collection_cannot_exceed_the_invoice_remainder(): void
    {
        $admin = $this->admin();
        $restaurant = $this->restaurant(['slug' => 'overpay-collection']);
        $invoice = $this->openInvoice($restaurant, 500, 450, 'PARTIALLY_PAID');

        $this->actingAs($admin)
            ->from("/admin/finance/restaurants/{$restaurant->id}")
            ->post("/admin/finance/restaurants/{$restaurant->id}/collections", [
                'invoice_id' => $invoice->id,
                'amount' => 100,
                'payment_method' => 'CASH',
            ])
            ->assertRedirect("/admin/finance/restaurants/{$restaurant->id}")
            ->assertSessionHasErrors('amount');

        $this->assertSame(450.0, (float) $invoice->fresh()->paid_amount);
        $this->assertDatabaseCount('collections', 0);
    }

    public function test_admin_can_record_a_collection_without_an_open_invoice(): void
    {
        $admin = $this->admin();
        $restaurant = $this->restaurant(['slug' => 'standalone-collection']);

        $this->actingAs($admin)
            ->post("/admin/finance/restaurants/{$restaurant->id}/collections", [
                'invoice_id' => '',
                'amount' => 150,
                'payment_method' => 'CASH',
                'notes' => 'تحصيل نقدي',
            ])
            ->assertRedirect(route('admin.finance.restaurants.show', $restaurant->id))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('collections', [
            'restaurant_id' => $restaurant->id,
            'invoice_id' => null,
            'payment_method' => 'CASH',
            'notes' => 'تحصيل نقدي',
            'collected_by_user_id' => $admin->id,
        ]);
    }

    public function test_collection_cannot_use_another_restaurants_invoice(): void
    {
        $admin = $this->admin();
        $restaurant = $this->restaurant(['slug' => 'own-collection']);
        $other = $this->restaurant(['slug' => 'other-collection', 'phone' => '01008887766']);
        $invoice = $this->openInvoice($other, 500);

        $this->actingAs($admin)
            ->from("/admin/finance/restaurants/{$restaurant->id}")
            ->post("/admin/finance/restaurants/{$restaurant->id}/collections", [
                'invoice_id' => $invoice->id,
                'amount' => 500,
                'payment_method' => 'CASH',
            ])
            ->assertRedirect("/admin/finance/restaurants/{$restaurant->id}")
            ->assertSessionHasErrors('invoice_id');

        $this->assertSame('ISSUED', $invoice->fresh()->status);
    }

    private function openInvoice(Restaurant $restaurant, float $total, float $paid = 0, string $status = 'ISSUED'): Invoice
    {
        return Invoice::query()->create([
            'invoice_number' => 'INV-COL-'.$restaurant->id.'-'.str_replace('.', '', (string) $total).$status,
            'restaurant_id' => $restaurant->id,
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-08',
            'subtotal' => $total,
            'tax_amount' => 0,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'status' => $status,
            'invoice_type' => 'SUBSCRIPTION',
        ]);
    }
}
