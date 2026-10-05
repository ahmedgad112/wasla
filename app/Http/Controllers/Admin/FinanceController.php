<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Collection;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\FinancialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    public function __construct(protected FinancialService $financialService) {}

    public function overview(Request $request): Response
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $summary = $this->financialService->getPlatformSummary($startDate, $endDate);
        $monthlyPnl = $this->financialService->getMonthlyProfitAndLoss();

        // ── Platform-only profits (our earnings from subscriptions + commissions) ──
        $platformProfit = [
            'subscription_revenue' => $summary['subscription_revenue'],
            'commission_revenue' => $summary['commission_revenue'],
            'total_platform_earn' => $summary['total_revenue'],
            'total_expenses' => $summary['total_expenses'],
            'net_profit' => $summary['net_profit'],
            'outstanding' => $summary['outstanding_receivables'],
        ];

        // ── Per-restaurant profits (their earnings from orders on the platform) ──
        $restaurants = Restaurant::select(
            'id', 'name', 'slug', 'status',
            'commission_type', 'commission_percentage', 'monthly_subscription_fee',
            'billing_cycle', 'subscription_ends_at', 'payment_due_date'
        )->get();

        $restaurantProfits = $restaurants->map(function ($r) {
            $base = Order::where('restaurant_id', $r->id);
            $delivered = (clone $base)->where('status', 'DELIVERED');
            $cancelled = (clone $base)->whereIn('status', ['CANCELLED', 'REJECTED']);
            $pending = (clone $base)->where('status', 'PENDING');

            $grossRevenue = (float) (clone $delivered)->sum('total_amount');
            $deliveryFees = (float) (clone $delivered)->sum('delivery_fee');
            $platformCut = (float) (clone $delivered)->sum('platform_commission_amount');
            // Net = what the restaurant actually earns (gross - delivery - platform cut)
            $netEarn = max(0, $grossRevenue - $deliveryFees - $platformCut);

            // Invoices & commission dues
            $invoices = Invoice::where('restaurant_id', $r->id)->where('status', '!=', 'CANCELLED')->get();
            $unpaidInvoices = $invoices->where('status', '!=', 'PAID');
            $dueAmount = $unpaidInvoices->sum(fn ($inv) => (float) ($inv->total_amount - $inv->paid_amount));
            $paidInvoicesAmount = $invoices->sum(fn ($inv) => (float) $inv->paid_amount);
            $overdueCount = $unpaidInvoices->count();
            $totalCollected = (float) Collection::where('restaurant_id', $r->id)->sum('amount');

            return [
                'id' => $r->id,
                'name' => $r->name,
                'slug' => $r->slug,
                'status' => $r->status,
                'commission_type' => $r->commission_type,
                'commission_rate' => (float) $r->commission_percentage,
                'subscription_fee' => (float) $r->monthly_subscription_fee,
                'billing_cycle' => $r->billing_cycle,
                'subscription_ends_at' => $r->subscription_ends_at?->toDateString(),
                'payment_due_date' => $r->payment_due_date?->toDateString(),
                'total_orders' => (clone $base)->count(),
                'delivered_orders' => (clone $delivered)->count(),
                'cancelled_orders' => (clone $cancelled)->count(),
                'pending_orders' => (clone $pending)->count(),
                'gross_revenue' => round($grossRevenue, 2),
                'delivery_fees' => round($deliveryFees, 2),
                'platform_cut' => round($platformCut, 2),
                'net_restaurant_earn' => round($netEarn, 2),
                'unpaid_due' => round($dueAmount, 2),
                'paid_amount' => round(max($paidInvoicesAmount, $totalCollected), 2),
                'overdue_count' => $overdueCount,
            ];
        })->toArray();

        // Total GMV across all delivered orders
        $totalGmv = Order::where('status', 'DELIVERED')->sum('total_amount');

        return Inertia::render('Admin/Finance/Overview', [
            'summary' => $summary,
            'platform_profit' => $platformProfit,
            'restaurant_profits' => $restaurantProfits,
            'monthly_pnl' => $monthlyPnl,
            'total_gmv' => (float) $totalGmv,
            'filters' => $request->only('start_date', 'end_date'),
        ]);
    }

    public function restaurantStatement(int $id): Response
    {
        $restaurant = Restaurant::query()->findOrFail($id);
        $invoices = $restaurant->invoices()
            ->where('status', '!=', 'CANCELLED')
            ->latest('issue_date')
            ->limit(20)
            ->get();
        $collections = $restaurant->collections()
            ->with('invoice:id,invoice_number')
            ->latest('collection_date')
            ->latest('id')
            ->limit(15)
            ->get();

        $unpaid = $invoices->where('status', '!=', 'PAID');

        return Inertia::render('Admin/Finance/RestaurantStatement', [
            'restaurant' => [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'status' => $restaurant->status,
                'commission_type' => $restaurant->commission_type,
                'commission_percentage' => (float) $restaurant->commission_percentage,
                'monthly_subscription_fee' => (float) $restaurant->monthly_subscription_fee,
                'billing_cycle' => $restaurant->billing_cycle ?: 'MONTHLY',
                'grace_period_days' => (int) ($restaurant->grace_period_days ?? 7),
                'subscription_starts_at' => $restaurant->subscription_starts_at?->toDateString(),
                'subscription_ends_at' => $restaurant->subscription_ends_at?->toDateString(),
                'payment_due_date' => $restaurant->payment_due_date?->toDateString(),
            ],
            'renewal' => $this->nextSubscriptionWindow($restaurant),
            'invoices' => $invoices->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'invoice_type' => $invoice->invoice_type,
                'status' => $invoice->status,
                'issue_date' => $invoice->issue_date?->toDateString(),
                'due_date' => $invoice->due_date?->toDateString(),
                'total_amount' => (float) $invoice->total_amount,
                'paid_amount' => (float) $invoice->paid_amount,
                'notes' => $invoice->notes,
            ])->values(),
            'collections' => $collections->map(fn (Collection $collection) => [
                'id' => $collection->id,
                'amount' => (float) $collection->amount,
                'payment_method' => $collection->payment_method,
                'collection_date' => $collection->collection_date?->toDateString(),
                'notes' => $collection->notes,
                'invoice_number' => $collection->invoice?->invoice_number,
            ])->values(),
            'dues' => [
                'subscription' => round((float) $unpaid->where('invoice_type', 'SUBSCRIPTION')->sum(fn (Invoice $invoice) => $invoice->total_amount - $invoice->paid_amount), 2),
                'commission' => round((float) $unpaid->where('invoice_type', '!=', 'SUBSCRIPTION')->sum(fn (Invoice $invoice) => $invoice->total_amount - $invoice->paid_amount), 2),
                'total' => round((float) $unpaid->sum(fn (Invoice $invoice) => $invoice->total_amount - $invoice->paid_amount), 2),
            ],
        ]);
    }

    public function renewSubscription(Request $request, int $id): RedirectResponse
    {
        $restaurant = Restaurant::query()->findOrFail($id);

        $request->merge([
            'billing_model' => $request->input('billing_model', 'subscription'),
        ]);

        $validated = $request->validate([
            'billing_model' => 'required|in:subscription,percentage',
            'price_mode' => 'required_if:billing_model,subscription|nullable|in:same,custom',
            'amount' => 'nullable|numeric|min:0.01',
            'collected_amount' => 'nullable|numeric|min:0.01',
            'commission_rate' => 'required_if:billing_model,percentage|nullable|numeric|min:0|max:100',
            'payment_method' => 'required_if:billing_model,subscription|nullable|in:CASH,BANK_TRANSFER,VODAFONE_CASH,INSTAPAY',
        ], [
            'billing_model.in' => 'اختَر اشتراك أو نسبة من كل طلب.',
            'price_mode.required_if' => 'حدد هل التجديد بنفس السعر أم بسعر جديد.',
            'price_mode.in' => 'اختيار السعر غير صالح.',
            'amount.numeric' => 'سعر الاشتراك لازم يكون رقم.',
            'amount.min' => 'سعر الاشتراك لازم يكون أكبر من صفر.',
            'collected_amount.numeric' => 'المبلغ المحصّل لازم يكون رقم.',
            'collected_amount.min' => 'المبلغ المحصّل لازم يكون أكبر من صفر.',
            'commission_rate.required_if' => 'حدد نسبة العمولة من كل طلب.',
            'commission_rate.numeric' => 'نسبة العمولة لازم تكون رقم.',
            'commission_rate.min' => 'نسبة العمولة لا يمكن أن تكون سالبة.',
            'commission_rate.max' => 'نسبة العمولة لا تتجاوز 100.',
            'payment_method.required_if' => 'اختر طريقة دفع التجديد.',
            'payment_method.in' => 'طريقة الدفع غير صالحة.',
        ]);

        if ($validated['billing_model'] === 'percentage') {
            return $this->switchRestaurantToPercentage($restaurant, (float) $validated['commission_rate']);
        }

        $amount = $validated['price_mode'] === 'same'
            ? round((float) $restaurant->monthly_subscription_fee, 2)
            : round((float) ($validated['amount'] ?? 0), 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => $validated['price_mode'] === 'same'
                    ? 'مفيش سعر اشتراك حالي. اختَر تغيير السعر وأدخل القيمة.'
                    : 'أدخل سعر الاشتراك الجديد.',
            ]);
        }

        if (! array_key_exists('collected_amount', $validated)) {
            $collected = $amount;
        } elseif ($validated['collected_amount'] === null) {
            throw ValidationException::withMessages([
                'collected_amount' => 'أدخل المبلغ المحصّل من الفاتورة.',
            ]);
        } else {
            $collected = round((float) $validated['collected_amount'], 2);
        }

        if ($collected > $amount) {
            throw ValidationException::withMessages([
                'collected_amount' => 'المبلغ المحصّل لا يمكن أن يتجاوز قيمة الفاتورة ('.$amount.' ج.م).',
            ]);
        }

        $window = $this->nextSubscriptionWindow($restaurant);
        $planLabel = $this->subscriptionPlanLabel($window['billing_cycle']);
        $isPaidInFull = $collected >= $amount;

        DB::transaction(function () use ($restaurant, $amount, $collected, $isPaidInFull, $validated, $window, $planLabel): void {
            $restaurant->update([
                'commission_type' => 'SUBSCRIPTION',
                'commission_percentage' => 0,
                'monthly_subscription_fee' => $amount,
                'billing_cycle' => $window['billing_cycle'],
                'grace_period_days' => $window['grace_days'],
                'subscription_starts_at' => $window['starts_at'],
                'subscription_ends_at' => $window['ends_at'],
                'payment_due_date' => $window['due_date'],
            ]);

            $invoice = Invoice::create([
                'invoice_number' => 'INV-'.date('Ymd').'-'.str_pad((string) (Invoice::count() + 1), 4, '0', STR_PAD_LEFT),
                'restaurant_id' => $restaurant->id,
                'issue_date' => now()->toDateString(),
                'due_date' => $window['due_date'],
                'subtotal' => $amount,
                'tax_amount' => 0,
                'total_amount' => $amount,
                'paid_amount' => $collected,
                'status' => $isPaidInFull ? 'PAID' : 'PARTIALLY_PAID',
                'invoice_type' => 'SUBSCRIPTION',
                'notes' => "تجديد اشتراك {$planLabel}",
            ]);

            $invoice->items()->create([
                'description' => "تجديد اشتراك {$planLabel} في المنصة",
                'amount' => $amount,
            ]);

            Collection::create([
                'restaurant_id' => $restaurant->id,
                'invoice_id' => $invoice->id,
                'amount' => $collected,
                'payment_method' => $validated['payment_method'],
                'collection_date' => now()->toDateString(),
                'notes' => $isPaidInFull
                    ? "تحصيل تجديد اشتراك {$planLabel}"
                    : "تحصيل جزء من تجديد اشتراك {$planLabel}",
                'collected_by_user_id' => auth()->id(),
            ]);

            if ($isPaidInFull) {
                $this->reactivateIfBillingIsClear($restaurant);
            }
        });

        ActivityLog::log('SUBSCRIPTION_RENEWED', 'Restaurant', $restaurant->id, null, [
            'amount' => $amount,
            'collected_amount' => $collected,
            'price_mode' => $validated['price_mode'],
            'ends_at' => $window['ends_at'],
        ]);

        $message = $isPaidInFull
            ? "تم تجديد اشتراك {$restaurant->name} وتحصيل الفاتورة بالكامل ({$amount} ج.م)."
            : "تم تجديد اشتراك {$restaurant->name}. اتحصّل {$collected} ج.م من فاتورة {$amount} ج.م، والمتبقي مستحق.";

        return redirect()
            ->route('admin.finance.restaurants.show', $restaurant->id)
            ->with('success', $message);
    }

    public function recordCollection(Request $request, int $id): RedirectResponse
    {
        $restaurant = Restaurant::query()->findOrFail($id);

        $validated = $request->validate([
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:CASH,BANK_TRANSFER,VODAFONE_CASH,INSTAPAY'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'invoice_id.exists' => 'الفاتورة غير موجودة.',
            'amount.required' => 'أدخل مبلغ التحصيل.',
            'amount.numeric' => 'مبلغ التحصيل لازم يكون رقم.',
            'amount.min' => 'مبلغ التحصيل لازم يكون أكبر من صفر.',
            'payment_method.required' => 'اختر طريقة الدفع.',
            'payment_method.in' => 'طريقة الدفع غير صالحة.',
        ]);

        $invoice = null;

        if (! empty($validated['invoice_id'])) {
            $invoice = Invoice::query()
                ->whereKey($validated['invoice_id'])
                ->where('restaurant_id', $restaurant->id)
                ->first();

            if (! $invoice || in_array($invoice->status, ['PAID', 'CANCELLED'], true)) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'الفاتورة لا تتبع هذا المطعم أو تم سدادها.',
                ]);
            }
        }

        $amount = round((float) $validated['amount'], 2);
        $remaining = $invoice
            ? round((float) $invoice->total_amount - (float) $invoice->paid_amount, 2)
            : null;

        if ($invoice && $amount > $remaining) {
            throw ValidationException::withMessages([
                'amount' => 'المبلغ أكبر من المتبقي على الفاتورة ('.$remaining.' ج.م).',
            ]);
        }

        DB::transaction(function () use ($restaurant, $invoice, $validated, $amount, $remaining): void {
            Collection::create([
                'restaurant_id' => $restaurant->id,
                'invoice_id' => $invoice?->id,
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'collection_date' => now()->toDateString(),
                'notes' => ($validated['notes'] ?? null) ?: ($invoice
                    ? "تحصيل فاتورة {$invoice->invoice_number}"
                    : 'تحصيل بدون فاتورة'),
                'collected_by_user_id' => auth()->id(),
            ]);

            if (! $invoice) {
                return;
            }

            $isPaidInFull = $amount >= $remaining;
            $invoice->update([
                'paid_amount' => $isPaidInFull
                    ? round((float) $invoice->total_amount, 2)
                    : round((float) $invoice->paid_amount + $amount, 2),
                'status' => $isPaidInFull ? 'PAID' : 'PARTIALLY_PAID',
            ]);

            if ($isPaidInFull) {
                $this->reactivateIfBillingIsClear($restaurant);
            }
        });

        ActivityLog::log('COLLECTION_RECORDED', 'Restaurant', $restaurant->id, null, [
            'invoice_id' => $invoice?->id,
            'amount' => $amount,
            'payment_method' => $validated['payment_method'],
        ]);

        $message = $invoice === null
            ? "تم تسجيل تحصيل {$amount} ج.م."
            : ($amount >= $remaining
                ? "تم تحصيل فاتورة {$invoice->invoice_number} بالكامل ({$amount} ج.م)."
                : "تم تسجيل تحصيل {$amount} ج.م من فاتورة {$invoice->invoice_number}.");

        return redirect()
            ->route('admin.finance.restaurants.show', $restaurant->id)
            ->with('success', $message);
    }

    private function switchRestaurantToPercentage(Restaurant $restaurant, float $commissionRate): RedirectResponse
    {
        $rate = round($commissionRate, 2);

        DB::transaction(function () use ($restaurant, $rate): void {
            $restaurant->update([
                'commission_type' => 'PERCENTAGE',
                'commission_percentage' => $rate,
                'monthly_subscription_fee' => 0,
                'subscription_starts_at' => null,
                'subscription_ends_at' => null,
            ]);

            $this->reactivateIfBillingIsClear($restaurant);
        });

        ActivityLog::log('BILLING_MODEL_CHANGED', 'Restaurant', $restaurant->id, null, [
            'billing_model' => 'percentage',
            'commission_percentage' => $rate,
        ]);

        return redirect()
            ->route('admin.finance.restaurants.show', $restaurant->id)
            ->with('success', "تم تحويل {$restaurant->name} إلى نسبة {$rate}% من كل طلب، من غير اشتراك جديد.");
    }

    private function reactivateIfBillingIsClear(Restaurant $restaurant): void
    {
        $hasUnpaid = Invoice::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereNotIn('status', ['PAID', 'CANCELLED'])
            ->exists();

        if (! $hasUnpaid && $restaurant->isBillingSuspended()) {
            $restaurant->update([
                'status' => 'ACTIVE',
                'billing_suspended_at' => null,
                'suspension_reason' => null,
            ]);
        }
    }

    /**
     * @return array{billing_cycle: string, starts_at: string, ends_at: string, due_date: string, grace_days: int, plan_label: string}
     */
    private function nextSubscriptionWindow(Restaurant $restaurant): array
    {
        $billingCycle = in_array($restaurant->billing_cycle, ['MONTHLY', 'QUARTERLY', 'SEMIANNUAL', 'YEARLY'], true)
            ? $restaurant->billing_cycle
            : 'MONTHLY';
        $months = match ($billingCycle) {
            'QUARTERLY' => 3,
            'SEMIANNUAL' => 6,
            'YEARLY' => 12,
            default => 1,
        };
        $graceDays = $restaurant->grace_period_days === null ? 7 : (int) $restaurant->grace_period_days;
        $startsAt = $restaurant->subscription_ends_at
            && $restaurant->subscription_ends_at->copy()->startOfDay()->greaterThanOrEqualTo(now()->startOfDay())
            ? $restaurant->subscription_ends_at->copy()->startOfDay()
            : now()->startOfDay();
        $endsAt = $startsAt->copy()->addMonths($months);

        return [
            'billing_cycle' => $billingCycle,
            'starts_at' => $startsAt->toDateString(),
            'ends_at' => $endsAt->toDateString(),
            'due_date' => $endsAt->copy()->addDays($graceDays)->toDateString(),
            'grace_days' => $graceDays,
            'plan_label' => $this->subscriptionPlanLabel($billingCycle),
        ];
    }

    private function subscriptionPlanLabel(string $plan): string
    {
        return match ($plan) {
            'QUARTERLY' => 'ربع سنوي (3 شهور)',
            'SEMIANNUAL' => 'نصف سنوي (6 شهور)',
            'YEARLY' => 'سنوي',
            default => 'شهري',
        };
    }

    public function revenue(Request $request): Response
    {
        $query = Order::with('restaurant:id,name')
            ->where('status', 'DELIVERED');

        if ($request->filled('restaurant_id')) {
            $query->where('restaurant_id', $request->restaurant_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return Inertia::render('Admin/Finance/Revenue', [
            'orders' => $query->latest()->paginate(20)->withQueryString(),
            'restaurants' => Restaurant::active()->get(['id', 'name']),
            'filters' => $request->only('restaurant_id', 'date_from', 'date_to'),
            'summary' => [
                'total' => $query->sum('total_amount'),
                'count' => $query->count(),
            ],
        ]);
    }

    public function expenses(Request $request): Response
    {
        $query = Expense::with(['category', 'creator'])
            ->latest('expense_date');

        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->category_id);
        }

        return Inertia::render('Admin/Finance/Expenses', [
            'expenses' => $query->paginate(20)->withQueryString(),
            'categories' => ExpenseCategory::all(),
            'total' => $query->sum('amount'),
            'filters' => $request->only('category_id'),
        ]);
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
            'expense_date' => 'required|date',
        ]);

        $validated['created_by_user_id'] = auth()->id();

        if ($request->hasFile('receipt_file')) {
            $validated['receipt_file'] = $request->file('receipt_file')->store('expenses/receipts', 'public');
        }

        Expense::create($validated);

        return back()->with('success', 'تم إضافة المصروف بنجاح.');
    }

    public function deleteExpense(int $id): RedirectResponse
    {
        Expense::findOrFail($id)->delete();

        return back()->with('success', 'تم حذف المصروف.');
    }

    public function profitLoss(Request $request): Response
    {
        $summary = $this->financialService->getPlatformSummary(
            $request->get('start_date'),
            $request->get('end_date')
        );
        $monthlyPnl = $this->financialService->getMonthlyProfitAndLoss();

        return Inertia::render('Admin/Finance/ProfitLoss', [
            'summary' => $summary,
            'monthly_pnl' => $monthlyPnl,
            'filters' => $request->only('start_date', 'end_date'),
        ]);
    }
}
