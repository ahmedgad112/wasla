<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DeliveryDriver;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryDriverController extends Controller
{
    public function index(Request $request): Response
    {
        $query = DeliveryDriver::with(['user:id,name,email', 'restaurant:id,name'])
            ->withCount([
                'assignedOrders as active_orders_count' => fn (Builder $orders) => $orders->whereIn('status', [
                    'ASSIGNED_TO_DRIVER',
                    'OUT_FOR_DELIVERY',
                ]),
            ])
            ->latest();

        $this->applyOwnerFilter($query, $request->input('restaurant_id'));

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function (Builder $drivers) use ($search) {
                $drivers->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $users) => $users->where('email', 'like', "%{$search}%"));
            });
        }

        $this->applyStatusFilter($query, $request->string('status')->toString());

        $scope = DeliveryDriver::query();
        $this->applyOwnerFilter($scope, $request->input('restaurant_id'));

        return Inertia::render('Admin/DeliveryDrivers/Index', [
            'drivers' => $query->paginate(12)->withQueryString(),
            'restaurants' => Restaurant::active()->get(['id', 'name']),
            'filters' => $request->only(['search', 'restaurant_id', 'status']),
            'stats' => [
                'total' => (clone $scope)->count(),
                'available' => (clone $scope)->where('is_active', true)->where('availability_status', 'AVAILABLE')->count(),
                'busy' => (clone $scope)->where('is_active', true)->where('availability_status', 'BUSY')->count(),
                'inactive' => (clone $scope)->where('is_active', false)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/DeliveryDrivers/Create', [
            'restaurants' => Restaurant::active()->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->input('affiliation') === 'platform') {
            $request->merge(['restaurant_id' => null]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => ['required', Password::min(8)],
            'affiliation' => ['required', Rule::in(['restaurant', 'platform'])],
            'restaurant_id' => ['nullable', 'required_if:affiliation,restaurant', 'exists:restaurants,id'],
            'vehicle_type' => ['nullable', Rule::in(['MOTORCYCLE', 'SCOOTER', 'CAR', 'BICYCLE', 'WALKING'])],
            'vehicle_plate' => 'nullable|string|max:30',
        ], [
            'email.unique' => 'البريد الإلكتروني مسجل مسبقاً.',
            'phone.unique' => 'رقم الهاتف مسجل مسبقاً.',
            'affiliation.required' => 'اختَر هل الكابتن تابع لمطعم ولا للموقع.',
            'affiliation.in' => 'جهة الكابتن غير صالحة.',
            'restaurant_id.required_if' => 'يجب تحديد المطعم التابع له الكابتن.',
            'restaurant_id.exists' => 'المطعم المحدد غير موجود.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => 'DELIVERY_DRIVER',
            'is_active' => true,
        ]);

        $user->assignRole('DELIVERY_DRIVER');

        $restaurantId = $validated['affiliation'] === 'platform' ? null : $validated['restaurant_id'];

        $driver = DeliveryDriver::create([
            'user_id' => $user->id,
            'restaurant_id' => $restaurantId,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'vehicle_type' => $validated['vehicle_type'] ?? 'MOTORCYCLE',
            'vehicle_plate' => $validated['vehicle_plate'] ?? null,
            'is_active' => true,
            'availability_status' => 'AVAILABLE',
        ]);

        ActivityLog::log('DELIVERY_DRIVER_CREATED', 'DeliveryDriver', $driver->id, null, [
            'restaurant_id' => $restaurantId,
            'affiliation' => $validated['affiliation'],
            'created_by' => 'admin',
        ]);

        $message = $restaurantId === null
            ? "تم إنشاء حساب الكابتن {$validated['name']} وهو تابع للموقع."
            : "تم إنشاء حساب الكابتن {$validated['name']} وتعيينه للمطعم بنجاح.";

        return redirect()->route('admin.delivery-drivers.index')
            ->with('success', $message);
    }

    public function destroy(int $id): RedirectResponse
    {
        $driver = DeliveryDriver::findOrFail($id);
        ActivityLog::log('DELIVERY_DRIVER_DELETED', 'DeliveryDriver', $driver->id);
        $driver->user()->delete();
        $driver->delete();

        return back()->with('success', 'تم حذف المندوب بنجاح.');
    }

    public function toggle(int $id): RedirectResponse
    {
        $driver = DeliveryDriver::findOrFail($id);
        $driver->update(['is_active' => ! $driver->is_active]);
        $driver->user->update(['is_active' => $driver->is_active]);

        return back()->with('success', $driver->is_active ? 'تم تفعيل المندوب.' : 'تم تعطيل المندوب.');
    }

    private function applyOwnerFilter(Builder $query, mixed $owner): void
    {
        if ($owner === null || $owner === '') {
            return;
        }

        if ($owner === 'platform') {
            $query->whereNull('restaurant_id');

            return;
        }

        $query->where('restaurant_id', (int) $owner);
    }

    private function applyStatusFilter(Builder $query, string $status): void
    {
        match ($status) {
            'available' => $query->where('is_active', true)->where('availability_status', 'AVAILABLE'),
            'busy' => $query->where('is_active', true)->where('availability_status', 'BUSY'),
            'offline' => $query->where('is_active', true)->where('availability_status', 'OFFLINE'),
            'inactive' => $query->where('is_active', false),
            default => null,
        };
    }
}
