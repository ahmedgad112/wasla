<?php

namespace Tests\Feature\Admin;

use App\Models\Invoice;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BillingIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_lock_suspends_only_restaurants_past_their_due_date(): void
    {
        $admin = $this->admin();
        $overdue = $this->restaurant('overdue-restaurant');
        $current = $this->restaurant('current-restaurant');
        $this->invoice($overdue, now()->subDay()->toDateString());
        $this->invoice($current, now()->addDays(7)->toDateString());

        $this->actingAs($admin)
            ->post('/admin/billing/auto-lock-overdue')
            ->assertRedirect();

        $this->assertSame('SUSPENDED', $overdue->fresh()->status);
        $this->assertSame('ACTIVE', $current->fresh()->status);
    }

    public function test_locking_one_restaurant_does_not_suspend_the_others(): void
    {
        $admin = $this->admin();
        $first = $this->restaurant('first-restaurant');
        $second = $this->restaurant('second-restaurant');
        $this->invoice($first, now()->subDay()->toDateString());
        $this->invoice($second, now()->subDay()->toDateString());

        $this->actingAs($admin)
            ->post("/admin/billing/restaurant/{$first->id}/suspend")
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertSame('SUSPENDED', $first->fresh()->status);
        $this->assertSame('ACTIVE', $second->fresh()->status);
    }

    public function test_auto_generate_creates_issued_invoices_due_in_the_future(): void
    {
        $admin = $this->admin();
        $restaurant = $this->restaurant('billed-restaurant', 250);

        $this->actingAs($admin)
            ->post('/admin/billing/auto-generate')
            ->assertRedirect();

        $invoice = Invoice::query()->where('restaurant_id', $restaurant->id)->first();
        $this->assertNotNull($invoice);
        $this->assertSame('ISSUED', $invoice->status);
        $this->assertTrue($invoice->due_date->isFuture());
    }

    public function test_a_collection_cannot_pay_another_restaurants_invoice(): void
    {
        $admin = $this->admin();
        $payer = $this->restaurant('payer-restaurant');
        $other = $this->restaurant('other-restaurant');
        $invoice = $this->invoice($other, now()->addDay()->toDateString(), 'ISSUED');

        $this->actingAs($admin)
            ->post('/admin/billing/collection', [
                'restaurant_id' => $payer->id,
                'invoice_id' => $invoice->id,
                'amount' => 100,
                'payment_method' => 'CASH',
                'collection_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('invoice_id');

        $this->assertSame('ISSUED', $invoice->fresh()->status);
        $this->assertSame('0.00', $invoice->fresh()->paid_amount);
    }

    public function test_removed_resource_pages_are_not_routed(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/invoices/1/edit')->assertNotFound();
        $this->actingAs($admin)->get('/admin/users/1')->assertMethodNotAllowed();
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

    private function restaurant(string $slug, float $fee = 0): Restaurant
    {
        return Restaurant::query()->create([
            'name' => $slug,
            'slug' => $slug,
            'address' => 'برج العرب',
            'status' => 'ACTIVE',
            'availability_status' => 'OPEN',
            'monthly_subscription_fee' => $fee,
            'commission_type' => 'PERCENTAGE',
            'commission_percentage' => 10,
        ]);
    }

    private function invoice(Restaurant $restaurant, string $dueDate, string $status = 'ISSUED'): Invoice
    {
        return Invoice::query()->create([
            'invoice_number' => 'INV-'.$restaurant->id.'-'.$dueDate,
            'restaurant_id' => $restaurant->id,
            'issue_date' => now()->toDateString(),
            'due_date' => $dueDate,
            'subtotal' => 100,
            'tax_amount' => 0,
            'total_amount' => 100,
            'paid_amount' => 0,
            'status' => $status,
            'invoice_type' => 'SUBSCRIPTION',
        ]);
    }
}
