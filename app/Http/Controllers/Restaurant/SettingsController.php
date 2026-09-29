<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\RestaurantStaff;
use App\Services\PublicCatalogCache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        $restaurant = auth()->user()->restaurant;
        abort_if(! $restaurant, 403);

        return Inertia::render('Restaurant/Settings/Index', [
            'restaurant' => $restaurant,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $restaurant = auth()->user()->restaurant;
        abort_if(! $restaurant, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'opening_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'closing_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'estimated_delivery_time' => 'nullable|integer|min:1',
            'delivery_fee' => 'nullable|numeric|min:0',
            'delivery_fee_per_km' => 'nullable|numeric|min:0',
            'delivery_base_fee' => 'nullable|numeric|min:0',
            'delivery_enabled' => 'nullable|boolean',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'opening_time.regex' => 'صيغة وقت الفتح غير صحيحة.',
            'closing_time.regex' => 'صيغة وقت الإغلاق غير صحيحة.',
            'logo.image' => 'شعار المطعم لازم يكون صورة.',
            'cover_image.image' => 'صورة الغلاف لازم تكون صورة.',
            'logo.max' => 'حجم شعار المطعم يجب ألا يتجاوز 5 ميجابايت.',
            'cover_image.max' => 'حجم صورة الغلاف يجب ألا يتجاوز 5 ميجابايت.',
        ]);

        if (isset($validated['opening_time'])) {
            $validated['opening_time'] = $this->normalizeTime($validated['opening_time']);
        }

        if (isset($validated['closing_time'])) {
            $validated['closing_time'] = $this->normalizeTime($validated['closing_time']);
        }

        unset($validated['logo'], $validated['cover_image']);

        if ($request->hasFile('logo')) {
            $this->deleteStoredImage($restaurant->logo);
            $validated['logo'] = $request->file('logo')->store("restaurants/{$restaurant->id}", 'public');
        }

        if ($request->hasFile('cover_image')) {
            $this->deleteStoredImage($restaurant->cover_image);
            $validated['cover_image'] = $request->file('cover_image')->store("restaurants/{$restaurant->id}", 'public');
        }

        // Only restaurant-managed delivery can change fees or availability.
        if (! $restaurant->managesOwnDelivery()) {
            unset(
                $validated['delivery_fee'],
                $validated['delivery_base_fee'],
                $validated['delivery_fee_per_km'],
                $validated['delivery_enabled'],
            );
        } else {
            $validated['delivery_enabled'] = $request->boolean('delivery_enabled', true);
        }

        $restaurant->update($validated);
        $this->forgetRestaurantShellCache($restaurant->id);
        PublicCatalogCache::forgetRestaurant($restaurant->slug);

        return back()->with('success', 'تم حفظ إعدادات المطعم.');
    }

    public function updateAvailability(Request $request): RedirectResponse
    {
        $restaurant = auth()->user()->restaurant;
        abort_if(! $restaurant, 403);

        if ($restaurant->status === 'SUSPENDED') {
            return back()->with('error', 'المطعم موقوف من الإدارة ولا يمكن تغيير حالته.');
        }

        if ($restaurant->status !== 'ACTIVE') {
            return back()->with('error', 'حساب المطعم غير مفعّل من الإدارة.');
        }

        $validated = $request->validate([
            'availability_status' => 'required|in:OPEN,BUSY,CLOSED',
        ]);

        $restaurant->update([
            'availability_status' => $validated['availability_status'],
        ]);

        $this->forgetRestaurantShellCache($restaurant->id);
        PublicCatalogCache::forgetAvailability();
        PublicCatalogCache::forgetRestaurant($restaurant->slug);

        $labels = [
            'OPEN' => 'مفتوح',
            'BUSY' => 'مشغول',
            'CLOSED' => 'مغلق',
        ];

        ActivityLog::log(
            'RESTAURANT_AVAILABILITY_UPDATED',
            'Restaurant',
            $restaurant->id,
            null,
            ['availability_status' => $validated['availability_status']]
        );

        return back()->with(
            'success',
            'تم تحديث حالة المطعم إلى: '.$labels[$validated['availability_status']]
        );
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }

    private function deleteStoredImage(?string $path): void
    {
        if (! $path || str_starts_with($path, 'http') || str_starts_with($path, '/')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function forgetRestaurantShellCache(int $restaurantId): void
    {
        $userIds = RestaurantStaff::query()
            ->where('restaurant_id', $restaurantId)
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            cache()->forget("user.{$userId}.shell_restaurant");
        }
    }
}
