<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DeliveryDriver;
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
    private function restaurant()
    {
        $restaurant = auth()->user()->restaurant;
        abort_if(! $restaurant, 403);

        return $restaurant;
    }

    public function index(Request $request): Response
    {
        $restaurant = $this->restaurant();

        $query = DeliveryDriver::query()
            ->where('restaurant_id', $restaurant->id)
            ->with('user:id,name,email')
            ->withCount([
                'assignedOrders as active_orders_count' => fn (Builder $orders) => $orders->whereIn('status', [
                    'ASSIGNED_TO_DRIVER',
                    'OUT_FOR_DELIVERY',
                ]),
            ])
            ->latest();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function (Builder $drivers) use ($search) {
                $drivers->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $this->applyStatusFilter($query, $request->string('status')->toString());

        $scope = DeliveryDriver::query()->where('restaurant_id', $restaurant->id);

        return Inertia::render('Restaurant/DeliveryDrivers/Index', [
            'drivers' => $query->paginate(12)->withQueryString(),
            'restaurant' => $restaurant,
            'filters' => $request->only(['search', 'status']),
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
        return Inertia::render('Restaurant/DeliveryDrivers/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $restaurant = $this->restaurant();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => ['required', Password::min(8)],
            'vehicle_type' => ['nullable', Rule::in(['MOTORCYCLE', 'SCOOTER', 'CAR', 'BICYCLE', 'WALKING'])],
            'vehicle_plate' => 'nullable|string|max:30',
        ], [
            'email.unique' => 'البريد الإلكتروني مسجل مسبقاً لدى مستخدم آخر.',
            'phone.unique' => 'رقم الهاتف مسجل مسبقاً لدى مستخدم آخر.',
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

        $driver = DeliveryDriver::create([
            'user_id' => $user->id,
            'restaurant_id' => $restaurant->id,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'vehicle_type' => $validated['vehicle_type'] ?? 'MOTORCYCLE',
            'vehicle_plate' => $validated['vehicle_plate'] ?? null,
            'is_active' => true,
            'availability_status' => 'AVAILABLE',
        ]);

        ActivityLog::log('DELIVERY_DRIVER_CREATED', 'DeliveryDriver', $driver->id, null, [
            'restaurant_id' => $restaurant->id,
        ]);

        return redirect()->route('restaurant.delivery-drivers.index')
            ->with('success', "تم إنشاء حساب المندوب {$validated['name']}.");
    }

    public function edit(int $id): Response
    {
        $restaurant = $this->restaurant();
        $driver = DeliveryDriver::where('restaurant_id', $restaurant->id)->with('user')->findOrFail($id);

        return Inertia::render('Restaurant/DeliveryDrivers/Edit', ['driver' => $driver]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $restaurant = $this->restaurant();
        $driver = DeliveryDriver::where('restaurant_id', $restaurant->id)->findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($driver->user_id)],
            'vehicle_type' => ['required', Rule::in(['MOTORCYCLE', 'SCOOTER', 'CAR', 'BICYCLE', 'WALKING'])],
            'vehicle_plate' => 'nullable|string|max:30',
        ], [
            'phone.unique' => 'رقم الهاتف مسجل مسبقاً لدى مستخدم آخر.',
        ]);
        $driver->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'vehicle_type' => $validated['vehicle_type'],
            'vehicle_plate' => $validated['vehicle_plate'] ?? null,
        ]);
        $driver->user->update(['name' => $validated['name'], 'phone' => $validated['phone']]);

        return back()->with('success', 'تم تحديث بيانات المندوب.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $restaurant = $this->restaurant();
        $driver = DeliveryDriver::where('restaurant_id', $restaurant->id)->findOrFail($id);
        $driver->user()->delete(); // Soft delete the user
        $driver->delete();

        return back()->with('success', 'تم حذف المندوب.');
    }

    public function toggle(int $id): RedirectResponse
    {
        $restaurant = $this->restaurant();
        $driver = DeliveryDriver::where('restaurant_id', $restaurant->id)->findOrFail($id);
        $driver->update(['is_active' => ! $driver->is_active]);
        $driver->user->update(['is_active' => $driver->is_active]);

        return back()->with('success', $driver->is_active ? 'تم تفعيل المندوب.' : 'تم تعطيل المندوب.');
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
