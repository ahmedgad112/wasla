<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Collection;
use App\Models\Invoice;
use App\Models\Restaurant;
use App\Models\RestaurantStaff;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\FinancialService;
use App\Services\PublicCatalogCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RestaurantController extends Controller
{
    public function __construct(protected FinancialService $financialService) {}

    public function index(Request $request): Response
    {
        $query = Restaurant::withCount(['orders', 'deliveryDrivers', 'staff'])
            ->latest();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return Inertia::render('Admin/Restaurants/Index', [
            'restaurants' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Restaurants/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'address' => 'required|string',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'delivery_provider' => 'required|in:PLATFORM,RESTAURANT,PICKUP',
            'delivery_fee' => 'nullable|numeric|min:0',
            'delivery_base_fee' => 'nullable|numeric|min:0',
            'delivery_fee_per_km' => 'nullable|numeric|min:0',
            'estimated_delivery_time' => 'nullable|integer|min:0',
            'billing_model' => 'nullable|in:subscription,percentage',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'commission_type' => 'nullable|in:PERCENTAGE,FIXED,SUBSCRIPTION,HYBRID,NONE',
            'subscription_plan' => 'nullable|in:MONTHLY,QUARTERLY,SEMIANNUAL,YEARLY',
            'subscription_amount' => 'nullable|numeric|min:0',
            'subscription_paid' => 'nullable|boolean',
            'grace_period_days' => 'nullable|integer|min:0|max:365',
            'payment_method' => 'nullable|in:CASH,BANK_TRANSFER,VODAFONE_CASH,INSTAPAY',
            'status' => 'nullable|in:ACTIVE,INACTIVE,PENDING',
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email|unique:users,email',
            'owner_password' => 'required|string|min:8',
        ], [
            'name.required' => 'اسم المطعم مطلوب.',
            'phone.required' => 'رقم هاتف المطعم مطلوب.',
            'address.required' => 'عنوان المطعم مطلوب.',
            'delivery_provider.required' => 'اختر طريقة الاستلام أو التوصيل.',
            'delivery_provider.in' => 'طريقة الاستلام أو التوصيل غير صالحة.',
            'owner_name.required' => 'اسم المالك مطلوب.',
            'owner_email.required' => 'بريد المالك مطلوب.',
            'owner_email.unique' => 'بريد المالك مسجل مسبقاً.',
            'owner_password.required' => 'كلمة مرور المالك مطلوبة.',
            'owner_password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
            'billing_model.in' => 'اختَر اشتراك أو نسبة من كل طلب.',
            'commission_rate.numeric' => 'نسبة العمولة لازم تكون رقم.',
            'commission_rate.min' => 'نسبة العمولة لا يمكن أن تكون سالبة.',
            'commission_rate.max' => 'نسبة العمولة لا تتجاوز 100.',
            'subscription_plan.in' => 'نوع الاشتراك غير صالح.',
            'grace_period_days.integer' => 'مدة السماح لازم تكون عدد أيام.',
            'grace_period_days.min' => 'مدة السماح لا يمكن أن تكون سالبة.',
            'grace_period_days.max' => 'مدة السماح لا تتجاوز 365 يوماً.',
            'payment_method.in' => 'طريقة دفع الاشتراك غير صالحة.',
        ]);

        $subscriptionPaid = $request->boolean('subscription_paid');
        $subscriptionAmount = round((float) ($validated['subscription_amount'] ?? 0), 2);
        $subscriptionPlan = $validated['subscription_plan'] ?? 'MONTHLY';
        $graceDays = array_key_exists('grace_period_days', $validated) && $validated['grace_period_days'] !== null
            ? (int) $validated['grace_period_days']
            : 7;
        $billingModel = $validated['billing_model'] ?? (
            ($subscriptionPaid || $subscriptionAmount > 0) ? 'subscription' : 'percentage'
        );

        if ($billingModel === 'percentage') {
            if (! array_key_exists('commission_rate', $validated) || $validated['commission_rate'] === null) {
                throw ValidationException::withMessages([
                    'commission_rate' => 'حدد نسبة العمولة من كل طلب.',
                ]);
            }

            $subscriptionPaid = false;
            $subscriptionAmount = 0.0;
        } elseif ($subscriptionAmount <= 0 && ! $subscriptionPaid) {
            throw ValidationException::withMessages([
                'subscription_amount' => 'أدخل قيمة الاشتراك.',
            ]);
        }

        if ($subscriptionPaid && $subscriptionAmount <= 0) {
            throw ValidationException::withMessages([
                'subscription_amount' => 'أدخل قيمة الاشتراك قبل تحديد أنه تم الدفع.',
            ]);
        }

        if ($subscriptionPaid && empty($validated['payment_method'])) {
            throw ValidationException::withMessages([
                'payment_method' => 'اختر طريقة دفع الاشتراك.',
            ]);
        }

        [$subscriptionMonths, $subscriptionLabel] = $this->subscriptionPlanDefinition($subscriptionPlan);
        $subscriptionStartsAt = now()->startOfDay();
        $subscriptionEndsAt = $subscriptionAmount > 0
            ? $subscriptionStartsAt->copy()->addMonths($subscriptionMonths)
            : null;
        $paymentDueDate = $subscriptionEndsAt?->copy()->addDays($graceDays);

        $platformDefaultFee = (float) SystemSetting::get('default_delivery_fee', 10);
        $deliveryProvider = $validated['delivery_provider'];
        $isPlatformDelivery = $deliveryProvider === 'PLATFORM';
        $isPickupOnly = $deliveryProvider === 'PICKUP';

        $restaurant = DB::transaction(function () use ($validated, $platformDefaultFee, $deliveryProvider, $isPlatformDelivery, $isPickupOnly, $billingModel, $subscriptionAmount, $subscriptionPlan, $graceDays, $subscriptionStartsAt, $subscriptionEndsAt, $paymentDueDate, $subscriptionPaid, $subscriptionLabel) {
            $restaurant = Restaurant::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']).'-'.Str::lower(Str::random(4)),
                'description' => $validated['description'] ?? null,
                'phone' => $validated['phone'],
                'whatsapp' => $validated['phone'],
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'],
                'opening_time' => $validated['opening_time'] ?? '08:00',
                'closing_time' => $validated['closing_time'] ?? '23:00',
                'minimum_order_amount' => $validated['minimum_order_amount'] ?? 0,
                'delivery_provider' => $deliveryProvider,
                'delivery_enabled' => ! $isPickupOnly,
                'delivery_fee' => $isPickupOnly
                    ? 0
                    : ($isPlatformDelivery
                        ? ($validated['delivery_fee'] ?? $platformDefaultFee)
                        : ($validated['delivery_fee'] ?? 0)),
                'delivery_base_fee' => $isPickupOnly
                    ? 0
                    : ($isPlatformDelivery
                        ? ($validated['delivery_base_fee'] ?? $validated['delivery_fee'] ?? $platformDefaultFee)
                        : ($validated['delivery_base_fee'] ?? $validated['delivery_fee'] ?? 10)),
                'delivery_fee_per_km' => $isPickupOnly
                    ? 0
                    : ($isPlatformDelivery
                        ? ($validated['delivery_fee_per_km'] ?? 0)
                        : ($validated['delivery_fee_per_km'] ?? 5)),
                'estimated_delivery_time' => $validated['estimated_delivery_time'] ?? 30,
                'commission_type' => $billingModel === 'subscription' ? 'SUBSCRIPTION' : 'PERCENTAGE',
                'commission_percentage' => $billingModel === 'subscription' ? 0 : $validated['commission_rate'],
                'monthly_subscription_fee' => $subscriptionAmount,
                'billing_cycle' => $subscriptionAmount > 0 ? $subscriptionPlan : 'MONTHLY',
                'grace_period_days' => $billingModel === 'subscription' ? $graceDays : null,
                'subscription_starts_at' => $subscriptionAmount > 0 ? $subscriptionStartsAt->toDateString() : null,
                'subscription_ends_at' => $subscriptionEndsAt?->toDateString(),
                'payment_due_date' => $paymentDueDate?->toDateString(),
                'status' => $validated['status'] ?? 'ACTIVE',
                'availability_status' => 'CLOSED',
            ]);

            $owner = User::create([
                'name' => $validated['owner_name'],
                'email' => $validated['owner_email'],
                'phone' => null,
                'password' => $validated['owner_password'],
                'role' => 'RESTAURANT_OWNER',
                'is_active' => true,
            ]);

            $owner->assignRole('RESTAURANT_OWNER');

            RestaurantStaff::create([
                'restaurant_id' => $restaurant->id,
                'user_id' => $owner->id,
                'role' => 'OWNER',
                'is_active' => true,
            ]);

            if ($subscriptionAmount > 0) {
                $this->recordOpeningSubscription(
                    $restaurant,
                    $subscriptionAmount,
                    $subscriptionLabel,
                    $subscriptionPaid,
                    $paymentDueDate->toDateString(),
                    $validated['payment_method'] ?? null,
                );
            }

            return $restaurant;
        });

        ActivityLog::log('RESTAURANT_CREATED', 'Restaurant', $restaurant->id, null, ['name' => $restaurant->name]);

        $message = "تم إنشاء المطعم \"{$restaurant->name}\" وحساب المالك بنجاح.";
        if ($subscriptionAmount > 0 && $subscriptionPaid) {
            $message .= ' وتم تسجيل الاشتراك المدفوع في الأرباح والتحصيل.';
        }

        return redirect()->route('admin.restaurants.index')
            ->with('success', $message);
    }

    public function show(int $id): Response|RedirectResponse
    {
        $restaurant = Restaurant::with(['staff.user', 'deliveryDrivers.user'])
            ->withCount(['orders', 'menuItems', 'categories'])
            ->find($id);

        if (! $restaurant) {
            return $this->missingRestaurantRedirect();
        }

        $financialSummary = $this->financialService->getPlatformSummary();
        $restaurantFinancial = collect($this->financialService->getRestaurantFinancialTable())
            ->firstWhere('id', $id);

        return Inertia::render('Admin/Restaurants/Show', [
            'restaurant' => $restaurant,
            'restaurant_financial' => $restaurantFinancial,
        ]);
    }

    public function edit(int $id): Response|RedirectResponse
    {
        $restaurant = Restaurant::find($id);

        if (! $restaurant) {
            return $this->missingRestaurantRedirect();
        }

        return Inertia::render('Admin/Restaurants/Edit', [
            'restaurant' => $restaurant,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $restaurant = Restaurant::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'delivery_provider' => 'nullable|in:PLATFORM,RESTAURANT,PICKUP',
            'delivery_enabled' => 'nullable|boolean',
            'delivery_fee' => 'nullable|numeric|min:0',
            'delivery_fee_per_km' => 'nullable|numeric|min:0',
            'delivery_base_fee' => 'nullable|numeric|min:0',
            'estimated_delivery_time' => 'nullable|integer|min:0',
            'student_discount_percentage' => 'nullable|numeric|min:0|max:100',
            'commission_type' => 'required|in:PERCENTAGE,FIXED,SUBSCRIPTION,HYBRID,NONE',
            'commission_percentage' => 'nullable|numeric|min:0',
            'monthly_subscription_fee' => 'nullable|numeric|min:0',
            'billing_cycle' => 'nullable|in:MONTHLY,QUARTERLY,SEMIANNUAL,YEARLY',
            'grace_period_days' => 'nullable|integer|min:0|max:365',
            'status' => 'required|in:ACTIVE,INACTIVE,PENDING,SUSPENDED',
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('restaurants/logos', 'public');
            $validated['logo'] = $path;
        }

        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('restaurants/covers', 'public');
            $validated['cover_image'] = $path;
        }

        $provider = $validated['delivery_provider'] ?? $restaurant->delivery_provider;

        if ($provider === 'PICKUP') {
            $validated['delivery_provider'] = 'PICKUP';
            $validated['delivery_enabled'] = false;
            $validated['delivery_fee'] = 0;
            $validated['delivery_base_fee'] = 0;
            $validated['delivery_fee_per_km'] = 0;
        } elseif ($provider === 'PLATFORM') {
            $validated['delivery_enabled'] = true;
            $validated['delivery_provider'] = 'PLATFORM';
        } elseif (array_key_exists('delivery_enabled', $validated) || $request->has('delivery_enabled')) {
            $validated['delivery_enabled'] = $request->boolean('delivery_enabled');
        }

        if (
            $restaurant->subscription_ends_at
            && array_key_exists('grace_period_days', $validated)
            && $validated['grace_period_days'] !== null
        ) {
            $validated['payment_due_date'] = $restaurant->subscription_ends_at
                ->copy()
                ->addDays((int) $validated['grace_period_days'])
                ->toDateString();
        }

        $restaurant->update($validated);
        ActivityLog::log('RESTAURANT_UPDATED', 'Restaurant', $restaurant->id);
        PublicCatalogCache::forgetRestaurant($restaurant->slug);

        return redirect()->route('admin.restaurants.index')
            ->with('success', 'تم تحديث بيانات المطعم بنجاح.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $restaurant = Restaurant::withTrashed()->find($id);

        if (! $restaurant || $restaurant->trashed()) {
            return $this->missingRestaurantRedirect();
        }

        $staffUserIds = RestaurantStaff::query()
            ->where('restaurant_id', $restaurant->id)
            ->pluck('user_id');

        DB::transaction(function () use ($restaurant, $staffUserIds): void {
            ActivityLog::log('RESTAURANT_DELETED', 'Restaurant', $restaurant->id, ['name' => $restaurant->name]);

            $driverUserIds = $restaurant->deliveryDrivers()->pluck('user_id');
            $restaurant->offers()->delete();
            $restaurant->categories()->delete();
            $restaurant->menuItems()->delete();
            $restaurant->deliveryDrivers()->update([
                'is_active' => false,
                'availability_status' => 'OFFLINE',
            ]);

            User::query()
                ->whereIn('id', $driverUserIds)
                ->where('role', 'DELIVERY_DRIVER')
                ->update(['is_active' => false]);

            $restaurant->update([
                'status' => 'INACTIVE',
                'availability_status' => 'CLOSED',
            ]);
            $restaurant->delete();

            User::query()
                ->whereIn('id', $staffUserIds)
                ->whereIn('role', ['RESTAURANT_OWNER', 'RESTAURANT_STAFF'])
                ->whereDoesntHave('restaurantStaff', function ($query) use ($restaurant) {
                    $query->where('restaurant_id', '!=', $restaurant->id);
                })
                ->update(['is_active' => false]);
        });

        PublicCatalogCache::forgetRestaurant($restaurant->slug);
        PublicCatalogCache::forgetListing();

        return redirect()->route('admin.restaurants.index')
            ->with('success', 'تم حذف المطعم بنجاح.');
    }

    private function missingRestaurantRedirect(): RedirectResponse
    {
        return redirect()
            ->route('admin.restaurants.index')
            ->with('error', 'هذا المطعم غير موجود أو تم حذفه من قبل.');
    }

    public function suspend(int $id): RedirectResponse
    {
        $restaurant = Restaurant::findOrFail($id);
        $restaurant->update(['status' => 'SUSPENDED']);
        ActivityLog::log('RESTAURANT_SUSPENDED', 'Restaurant', $restaurant->id);
        PublicCatalogCache::forgetRestaurant($restaurant->slug);

        return back()->with('success', "تم تعليق المطعم \"{$restaurant->name}\".");
    }

    public function activate(int $id): RedirectResponse
    {
        $restaurant = Restaurant::findOrFail($id);
        $restaurant->update(['status' => 'ACTIVE']);
        ActivityLog::log('RESTAURANT_ACTIVATED', 'Restaurant', $restaurant->id);
        PublicCatalogCache::forgetRestaurant($restaurant->slug);

        return back()->with('success', "تم تفعيل المطعم \"{$restaurant->name}\".");
    }

    public function updateFinancialConfig(Request $request, int $id): RedirectResponse
    {
        $restaurant = Restaurant::findOrFail($id);

        $validated = $request->validate([
            'commission_type' => 'required|in:PERCENTAGE,FIXED,SUBSCRIPTION,HYBRID,NONE',
            'commission_percentage' => 'nullable|numeric|min:0|max:100',
            'monthly_subscription_fee' => 'nullable|numeric|min:0',
            'billing_cycle' => 'nullable|string',
            'payment_due_date' => 'nullable|date',
        ]);

        $oldValues = $restaurant->only('commission_type', 'commission_percentage', 'monthly_subscription_fee');
        $restaurant->update($validated);

        ActivityLog::log('RESTAURANT_FINANCIAL_CONFIG_UPDATED', 'Restaurant', $restaurant->id, $oldValues, $validated);

        // Clear financial cache
        PublicCatalogCache::forgetListing();

        return back()->with('success', 'تم تحديث الإعدادات المالية للمطعم.');
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function subscriptionPlanDefinition(string $plan): array
    {
        return match ($plan) {
            'QUARTERLY' => [3, 'ربع سنوي (3 شهور)'],
            'SEMIANNUAL' => [6, 'نصف سنوي (6 شهور)'],
            'YEARLY' => [12, 'سنوي'],
            default => [1, 'شهري'],
        };
    }

    private function recordOpeningSubscription(
        Restaurant $restaurant,
        float $amount,
        string $planLabel,
        bool $paid,
        string $dueDate,
        ?string $paymentMethod,
    ): void {
        $invoice = Invoice::create([
            'invoice_number' => 'INV-'.date('Ymd').'-'.str_pad((string) (Invoice::count() + 1), 4, '0', STR_PAD_LEFT),
            'restaurant_id' => $restaurant->id,
            'issue_date' => now()->toDateString(),
            'due_date' => $dueDate,
            'subtotal' => $amount,
            'tax_amount' => 0,
            'total_amount' => $amount,
            'paid_amount' => $paid ? $amount : 0,
            'status' => $paid ? 'PAID' : 'ISSUED',
            'invoice_type' => 'SUBSCRIPTION',
            'notes' => $paid
                ? "اشتراك {$planLabel} تم تحصيله عند إنشاء المطعم"
                : "اشتراك {$planLabel} بانتظار التحصيل",
        ]);

        $invoice->items()->create([
            'description' => "اشتراك {$planLabel} في المنصة",
            'amount' => $amount,
        ]);

        if (! $paid) {
            return;
        }

        Collection::create([
            'restaurant_id' => $restaurant->id,
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'payment_method' => $paymentMethod ?? 'CASH',
            'collection_date' => now()->toDateString(),
            'notes' => "تحصيل اشتراك {$planLabel} عند إضافة المطعم",
            'collected_by_user_id' => auth()->id(),
        ]);
    }
}
