<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
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
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'commission_type' => 'nullable|in:PERCENTAGE,FIXED,SUBSCRIPTION,HYBRID,NONE',
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
        ]);

        $platformDefaultFee = (float) SystemSetting::get('default_delivery_fee', 10);
        $deliveryProvider = $validated['delivery_provider'];
        $isPlatformDelivery = $deliveryProvider === 'PLATFORM';
        $isPickupOnly = $deliveryProvider === 'PICKUP';

        $restaurant = DB::transaction(function () use ($validated, $platformDefaultFee, $deliveryProvider, $isPlatformDelivery, $isPickupOnly) {
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
                'commission_type' => $validated['commission_type'] ?? 'PERCENTAGE',
                'commission_percentage' => $validated['commission_rate'] ?? 15,
                'status' => $validated['status'] ?? 'ACTIVE',
                'availability_status' => 'CLOSED',
                'billing_cycle' => 'MONTHLY',
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

            return $restaurant;
        });

        ActivityLog::log('RESTAURANT_CREATED', 'Restaurant', $restaurant->id, null, ['name' => $restaurant->name]);

        return redirect()->route('admin.restaurants.index')
            ->with('success', "تم إنشاء المطعم \"{$restaurant->name}\" وحساب المالك بنجاح.");
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
}
