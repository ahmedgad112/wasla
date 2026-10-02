<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Customer::with('user')
            ->latest();

        if ($request->filled('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('student_status')) {
            $query->where('student_status', $request->student_status);
        }

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $query->withCount('orders')->paginate(20)->withQueryString(),
            'filters' => $request->only('search', 'student_status'),
        ]);
    }

    public function show(int $id): Response
    {
        $customer = Customer::with(['user', 'addresses'])
            ->withCount('orders')
            ->findOrFail($id);

        $recentOrders = $customer->orders()
            ->with('restaurant:id,name')
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Admin/Customers/Show', [
            'customer' => $customer,
            'recent_orders' => $recentOrders,
        ]);
    }

    public function verifyStudent(int $id): RedirectResponse
    {
        $customer = Customer::findOrFail($id);
        $customer->update([
            'student_status' => 'APPROVED',
            'student_verified_at' => now(),
        ]);
        ActivityLog::log('STUDENT_VERIFIED', 'Customer', $customer->id);

        return back()->with('success', 'تم تأكيد هوية الطالب وتفعيل الخصم.');
    }

    public function rejectStudent(int $id): RedirectResponse
    {
        $customer = Customer::findOrFail($id);
        $customer->update(['student_status' => 'REJECTED']);
        ActivityLog::log('STUDENT_REJECTED', 'Customer', $customer->id);

        return back()->with('success', 'تم رفض طلب التحقق من الهوية الطلابية.');
    }

    public function toggleActive(int $id): RedirectResponse
    {
        $customer = Customer::with('user')->findOrFail($id);
        $user = $customer->user;

        if ($user === null || ! $user->isCustomer()) {
            return back()->with('error', 'لا يمكن تغيير حالة هذا الحساب.');
        }

        $user->update(['is_active' => ! $user->is_active]);
        ActivityLog::log('CUSTOMER_TOGGLE_ACTIVE', 'Customer', $customer->id, null, [
            'is_active' => $user->is_active,
            'user_id' => $user->id,
        ]);

        return back()->with(
            'success',
            $user->is_active
                ? 'تم تفعيل حساب العميل.'
                : 'تم إيقاف حساب العميل. لن يقدر يسجّل الدخول.'
        );
    }

    public function loginAs(int $id, AuthService $authService): RedirectResponse
    {
        $customer = Customer::with('user')->findOrFail($id);
        $user = $customer->user;

        if ($user === null || ! $user->isCustomer() || ! $user->is_active) {
            return back()->with('error', 'لا يمكن تسجيل الدخول بهذا الحساب.');
        }

        $authService->startCustomerImpersonation($user);

        return redirect()->route('customer.dashboard');
    }
}
