<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Restaurant extends Model
{
    use HasFactory, SoftDeletes;

    protected $appends = [
        'availability_label',
        'is_open',
    ];

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'cover_image',
        'description',
        'phone',
        'whatsapp',
        'email',
        'address',
        'latitude',
        'longitude',
        'status', // ACTIVE, INACTIVE, SUSPENDED, PENDING
        'availability_status', // OPEN, BUSY, CLOSED
        'opening_time',
        'closing_time',
        'minimum_order_amount',
        'delivery_fee',
        'delivery_base_fee',
        'delivery_fee_per_km',
        'estimated_delivery_time',
        'delivery_provider', // PLATFORM, RESTAURANT, PICKUP
        'delivery_enabled',
        'student_discount_percentage',
        'commission_type',
        'commission_percentage',
        'monthly_subscription_fee',
        'billing_cycle',
        'grace_period_days',
        'subscription_starts_at',
        'subscription_ends_at',
        'payment_due_date',
        'billing_suspended_at',
        'suspension_reason',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'minimum_order_amount' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'delivery_base_fee' => 'decimal:2',
            'delivery_fee_per_km' => 'decimal:2',
            'delivery_enabled' => 'boolean',
            'student_discount_percentage' => 'decimal:2',
            'commission_percentage' => 'decimal:2',
            'monthly_subscription_fee' => 'decimal:2',
            'grace_period_days' => 'integer',
            'subscription_starts_at' => 'date',
            'subscription_ends_at' => 'date',
            'payment_due_date' => 'date',
            'billing_suspended_at' => 'datetime',
        ];
    }

    public function staff(): HasMany
    {
        return $this->hasMany(RestaurantStaff::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('sort_order');
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function deliveryDrivers(): HasMany
    {
        return $this->hasMany(DeliveryDriver::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function financialRecords(): HasMany
    {
        return $this->hasMany(FinancialRecord::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function isBusy(): bool
    {
        return $this->status === 'ACTIVE' && $this->availability_status === 'BUSY';
    }

    public function isAcceptingOrders(): bool
    {
        return $this->status === 'ACTIVE'
            && in_array($this->availability_status, ['OPEN', 'BUSY'], true);
    }

    public function managesOwnDelivery(): bool
    {
        return $this->delivery_provider === 'RESTAURANT';
    }

    public function isPlatformDelivery(): bool
    {
        return $this->delivery_provider === 'PLATFORM';
    }

    public function isPickupOnly(): bool
    {
        return $this->delivery_provider === 'PICKUP';
    }

    public function isDeliveryAvailable(): bool
    {
        if ($this->isPickupOnly()) {
            return false;
        }

        if ($this->isPlatformDelivery()) {
            return true;
        }

        return (bool) $this->delivery_enabled;
    }

    public function isBillingSuspended(): bool
    {
        return $this->status === 'SUSPENDED' && $this->billing_suspended_at !== null;
    }

    public function subscriptionInvoiceAlreadyCoversToday(): bool
    {
        if ($this->subscription_ends_at === null) {
            return false;
        }

        $allowanceEnds = $this->payment_due_date ?? $this->subscription_ends_at;

        return $allowanceEnds->copy()->endOfDay()->greaterThanOrEqualTo(now());
    }

    /**
     * Access ends the day after the due date, so the due date itself stays usable.
     * A paid subscription with no open invoice still expires after its end date plus grace days.
     */
    public function billingAccessExpired(): bool
    {
        if (! $this->dateHasPassed($this->payment_due_date)) {
            return false;
        }

        $hasUnpaidInvoice = $this->invoices()
            ->whereNotIn('status', ['PAID', 'CANCELLED'])
            ->whereDate('due_date', '<', now()->toDateString())
            ->exists();

        if ($hasUnpaidInvoice) {
            return true;
        }

        if ((float) $this->monthly_subscription_fee <= 0 || $this->subscription_ends_at === null) {
            return false;
        }

        $coverageEnds = $this->subscription_ends_at
            ->copy()
            ->addDays((int) ($this->grace_period_days ?? 0));

        return $this->dateHasPassed($coverageEnds);
    }

    private function dateHasPassed(mixed $date): bool
    {
        if ($date === null) {
            return false;
        }

        return Carbon::parse($date)->startOfDay()->lt(now()->startOfDay());
    }

    protected function availabilityLabel(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->status !== 'ACTIVE') {
                return 'مغلق';
            }

            return match ($this->availability_status) {
                'OPEN' => 'مفتوح',
                'BUSY' => 'مشغول',
                default => 'مغلق',
            };
        });
    }

    /**
     * Compatibility flag: restaurant can receive orders (open or busy).
     */
    protected function isOpen(): Attribute
    {
        return Attribute::get(
            fn (): bool => $this->isAcceptingOrders()
        );
    }
}
