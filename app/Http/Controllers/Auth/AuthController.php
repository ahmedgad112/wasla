<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
            'remember' => 'sometimes|boolean',
        ]);

        $user = $this->authService->login(
            $request->email,
            $request->password,
            $request->boolean('remember')
        );

        return redirect()->route($this->authService->dashboardRouteName($user));
    }

    public function showCustomerRegister(): Response
    {
        return Inertia::render('Auth/CustomerRegister');
    }

    public function customerRegister(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->numbers()->symbols(),
                'regex:/[A-Z]/',
            ],
        ], [
            'email.unique' => 'البريد الإلكتروني مسجل مسبقاً لدى مستخدم آخر.',
            'phone.unique' => 'رقم الهاتف مسجل مسبقاً لدى مستخدم آخر.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
            'password.numbers' => 'كلمة المرور لازم فيها رقم على الأقل.',
            'password.symbols' => 'كلمة المرور لازم فيها رمز على الأقل (مثل @ أو # أو !).',
            'password.regex' => 'كلمة المرور لازم فيها حرف كبير (Capital) على الأقل.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'CUSTOMER',
            'is_active' => true,
        ]);

        // Create Customer profile
        Customer::create([
            'user_id' => $user->id,
        ]);

        // Assign Spatie role
        $user->assignRole('CUSTOMER');

        auth()->login($user);
        $request->session()->regenerate();

        return redirect()->route('customer.dashboard');
    }

    public function logout(): RedirectResponse
    {
        if (session()->has('impersonator_id')) {
            return $this->leaveImpersonation();
        }

        $this->authService->logout();

        return redirect()->route('login');
    }

    public function leaveImpersonation(): RedirectResponse
    {
        if (! session()->has('impersonator_id')) {
            return redirect()->route('home');
        }

        if (! $this->authService->stopImpersonation()) {
            return redirect()->route('login')->with('error', 'تعذر الرجوع لحساب الإدارة.');
        }

        return redirect()
            ->route('admin.customers.index')
            ->with('success', 'رجعت لحساب الإدارة.');
    }
}
