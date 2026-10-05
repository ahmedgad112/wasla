<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DeliveryDriver;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $period = $this->resolvePeriod($request->string('period')->toString());
        $orders = $this->ordersInPeriod($period['start'], $period['end']);
        $statusRows = $this->statusRows($orders);
        $delivered = $this->deliveredMoney($orders);
        $buckets = $this->statusBuckets($statusRows);
        $totalOrders = (int) collect($buckets)->sum('count');
        $deliveredCount = (int) collect($buckets)->firstWhere('key', 'delivered')['count'];
        $stoppedCount = (int) collect($buckets)->firstWhere('key', 'stopped')['count'];

        return Inertia::render('Admin/Reports/Index', [
            'period' => [
                'key' => $period['key'],
                'label' => $period['label'],
                'from' => $period['start']?->toDateString(),
                'to' => $period['end']?->toDateString(),
            ],
            'platform' => $this->platformMoney($period['start'], $period['end']),
            'orders' => [
                'total' => $totalOrders,
                'buckets' => $buckets,
                'completion_rate' => $this->rate($deliveredCount, $totalOrders),
                'cancellation_rate' => $this->rate($stoppedCount, $totalOrders),
            ],
            'delivered_money' => $delivered,
            'payments' => $this->payments($orders),
            'restaurants' => $this->restaurants($period['start'], $period['end']),
            'customers' => $this->customers($orders, $period['start'], $period['end']),
            'delivery' => $this->delivery($orders, $delivered['delivery_fees'], (int) collect($buckets)->firstWhere('key', 'on_the_way')['count']),
            'expenses' => $this->expenses($period['start'], $period['end']),
            'daily' => $this->daily($period['start'], $period['end']),
            'top_items' => $this->topItems($period['start'], $period['end']),
        ]);
    }

    /**
     * @return array{key: string, label: string, start: ?Carbon, end: ?Carbon}
     */
    private function resolvePeriod(string $key): array
    {
        $key = in_array($key, ['today', 'week', 'month', 'all'], true) ? $key : 'month';
        $now = now();

        return match ($key) {
            'today' => [
                'key' => 'today',
                'label' => 'اليوم',
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
            ],
            'week' => [
                'key' => 'week',
                'label' => 'هذا الأسبوع',
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfDay(),
            ],
            'all' => [
                'key' => 'all',
                'label' => 'كل الوقت',
                'start' => null,
                'end' => null,
            ],
            default => [
                'key' => 'month',
                'label' => 'هذا الشهر',
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfDay(),
            ],
        };
    }

    /**
     * @return Builder<Order>
     */
    private function ordersInPeriod(?Carbon $start, ?Carbon $end): Builder
    {
        return Order::query()->when(
            $start && $end,
            fn (Builder $query) => $query->whereBetween('created_at', [$start, $end]),
        );
    }

    /**
     * @param  Builder<Order>  $orders
     * @return Collection<string, object>
     */
    private function statusRows(Builder $orders): Collection
    {
        return (clone $orders)
            ->selectRaw('status, COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as amount')
            ->groupBy('status')
            ->get()
            ->keyBy(fn (Order $order): string => (string) $order->status);
    }

    /**
     * @param  Collection<string, object>  $rows
     * @return list<array{key: string, label: string, hint: string, count: int, amount: float}>
     */
    private function statusBuckets(Collection $rows): array
    {
        $groups = [
            ['key' => 'new', 'label' => 'جديدة', 'hint' => 'لسه المطعم ما أكدهاش', 'statuses' => ['PENDING']],
            ['key' => 'kitchen', 'label' => 'في المطبخ', 'hint' => 'اتأكدت، بتتجهز، أو جاهزة للاستلام', 'statuses' => ['CONFIRMED', 'PREPARING', 'READY_FOR_PICKUP']],
            ['key' => 'on_the_way', 'label' => 'مع الكابتن', 'hint' => 'اتسلمت للكابتن أو خارجة للتوصيل', 'statuses' => ['ASSIGNED_TO_DRIVER', 'OUT_FOR_DELIVERY']],
            ['key' => 'delivered', 'label' => 'تم التسليم', 'hint' => 'العميل استلم الطلب', 'statuses' => ['DELIVERED']],
            ['key' => 'stopped', 'label' => 'ملغية أو مرفوضة', 'hint' => 'اتلغت أو اترفضت أو اترجعت', 'statuses' => ['CANCELLED', 'REJECTED', 'REFUNDED']],
        ];

        return array_map(function (array $group) use ($rows): array {
            $count = 0;
            $amount = 0.0;

            foreach ($group['statuses'] as $status) {
                $row = $rows->get($status);
                $count += (int) ($row->order_count ?? 0);
                $amount += (float) ($row->amount ?? 0);
            }

            return [
                'key' => $group['key'],
                'label' => $group['label'],
                'hint' => $group['hint'],
                'count' => $count,
                'amount' => round($amount, 2),
            ];
        }, $groups);
    }

    /**
     * @param  Builder<Order>  $orders
     * @return array{orders: int, customer_paid: float, food_subtotal: float, discounts: float, student_discounts: float, delivery_fees: float, service_fees: float, platform_commission: float, restaurant_net: float, average_order: float}
     */
    private function deliveredMoney(Builder $orders): array
    {
        $row = (clone $orders)
            ->where('status', 'DELIVERED')
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as customer_paid')
            ->selectRaw('COALESCE(SUM(subtotal), 0) as food_subtotal')
            ->selectRaw('COALESCE(SUM(discount_amount), 0) as discounts')
            ->selectRaw('COALESCE(SUM(student_discount_amount), 0) as student_discounts')
            ->selectRaw('COALESCE(SUM(delivery_fee), 0) as delivery_fees')
            ->selectRaw('COALESCE(SUM(service_fee), 0) as service_fees')
            ->selectRaw('COALESCE(SUM(platform_commission_amount), 0) as platform_commission')
            ->first();

        $count = (int) ($row->order_count ?? 0);
        $customerPaid = round((float) ($row->customer_paid ?? 0), 2);
        $deliveryFees = round((float) ($row->delivery_fees ?? 0), 2);
        $commission = round((float) ($row->platform_commission ?? 0), 2);

        return [
            'orders' => $count,
            'customer_paid' => $customerPaid,
            'food_subtotal' => round((float) ($row->food_subtotal ?? 0), 2),
            'discounts' => round((float) ($row->discounts ?? 0), 2),
            'student_discounts' => round((float) ($row->student_discounts ?? 0), 2),
            'delivery_fees' => $deliveryFees,
            'service_fees' => round((float) ($row->service_fees ?? 0), 2),
            'platform_commission' => $commission,
            'restaurant_net' => round($customerPaid - $deliveryFees - $commission, 2),
            'average_order' => $count > 0 ? round($customerPaid / $count, 2) : 0,
        ];
    }

    /**
     * @return array{subscription_collected: float, commission_collected: float, other_collected: float, collected_total: float, expenses: float, net_profit: float, outstanding: float}
     */
    private function platformMoney(?Carbon $start, ?Carbon $end): array
    {
        $paid = Invoice::query()
            ->where('status', 'PAID')
            ->when($start && $end, fn (Builder $query) => $query->whereBetween('created_at', [$start, $end]));

        $subscription = round((float) (clone $paid)->where('invoice_type', 'SUBSCRIPTION')->sum('total_amount'), 2);
        $commission = round((float) (clone $paid)->where('invoice_type', 'COMMISSION')->sum('total_amount'), 2);
        $other = round((float) (clone $paid)->whereNotIn('invoice_type', ['SUBSCRIPTION', 'COMMISSION'])->sum('total_amount'), 2);
        $collected = round($subscription + $commission + $other, 2);
        $expenses = round((float) $this->expensesQuery($start, $end)->sum('amount'), 2);
        $outstanding = round(max(0, (float) Invoice::query()
            ->whereNotIn('status', ['PAID', 'CANCELLED'])
            ->sum(DB::raw('total_amount - paid_amount'))), 2);

        return [
            'subscription_collected' => $subscription,
            'commission_collected' => $commission,
            'other_collected' => $other,
            'collected_total' => $collected,
            'expenses' => $expenses,
            'net_profit' => round($collected - $expenses, 2),
            'outstanding' => $outstanding,
        ];
    }

    /**
     * @param  Builder<Order>  $orders
     * @return list<array{method: string, label: string, count: int, amount: float}>
     */
    private function payments(Builder $orders): array
    {
        $labels = [
            'CASH_ON_DELIVERY' => 'كاش عند الاستلام',
            'ONLINE_CARD' => 'بطاقة',
            'WALLET' => 'محفظة',
        ];

        return (clone $orders)
            ->where('status', 'DELIVERED')
            ->selectRaw("COALESCE(payment_method, 'OTHER') as payment_method, COUNT(*) as order_count, COALESCE(SUM(total_amount), 0) as amount")
            ->groupByRaw("COALESCE(payment_method, 'OTHER')")
            ->orderByDesc('amount')
            ->get()
            ->map(function (Order $order) use ($labels): array {
                $method = (string) $order->getAttribute('payment_method');

                return [
                    'method' => $method,
                    'label' => $labels[$method] ?? 'طريقة أخرى',
                    'count' => (int) $order->getAttribute('order_count'),
                    'amount' => round((float) $order->getAttribute('amount'), 2),
                ];
            })
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, status: string, total_orders: int, delivered_orders: int, cancelled_orders: int, customer_paid: float, delivery_fees: float, platform_commission: float, restaurant_net: float, unpaid_due: float}>
     */
    private function restaurants(?Carbon $start, ?Carbon $end): array
    {
        $dues = Invoice::query()
            ->whereNotIn('status', ['PAID', 'CANCELLED'])
            ->selectRaw('restaurant_id, COALESCE(SUM(total_amount - paid_amount), 0) as due')
            ->groupBy('restaurant_id')
            ->pluck('due', 'restaurant_id');

        return Restaurant::query()
            ->leftJoin('orders', function ($join) use ($start, $end): void {
                $join->on('orders.restaurant_id', '=', 'restaurants.id')
                    ->whereNull('orders.deleted_at');

                if ($start && $end) {
                    $join->whereBetween('orders.created_at', [$start, $end]);
                }
            })
            ->select('restaurants.id', 'restaurants.name', 'restaurants.status')
            ->selectRaw('COUNT(orders.id) as total_orders')
            ->selectRaw("SUM(CASE WHEN orders.status = 'DELIVERED' THEN 1 ELSE 0 END) as delivered_orders")
            ->selectRaw("SUM(CASE WHEN orders.status IN ('CANCELLED', 'REJECTED', 'REFUNDED') THEN 1 ELSE 0 END) as cancelled_orders")
            ->selectRaw("COALESCE(SUM(CASE WHEN orders.status = 'DELIVERED' THEN orders.total_amount ELSE 0 END), 0) as customer_paid")
            ->selectRaw("COALESCE(SUM(CASE WHEN orders.status = 'DELIVERED' THEN orders.delivery_fee ELSE 0 END), 0) as delivery_fees")
            ->selectRaw("COALESCE(SUM(CASE WHEN orders.status = 'DELIVERED' THEN orders.platform_commission_amount ELSE 0 END), 0) as platform_commission")
            ->groupBy('restaurants.id', 'restaurants.name', 'restaurants.status')
            ->orderByDesc('customer_paid')
            ->orderBy('restaurants.name')
            ->get()
            ->map(function (Restaurant $restaurant) use ($dues): array {
                $paid = round((float) $restaurant->getAttribute('customer_paid'), 2);
                $fees = round((float) $restaurant->getAttribute('delivery_fees'), 2);
                $commission = round((float) $restaurant->getAttribute('platform_commission'), 2);

                return [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                    'status' => (string) $restaurant->status,
                    'total_orders' => (int) $restaurant->getAttribute('total_orders'),
                    'delivered_orders' => (int) $restaurant->getAttribute('delivered_orders'),
                    'cancelled_orders' => (int) $restaurant->getAttribute('cancelled_orders'),
                    'customer_paid' => $paid,
                    'delivery_fees' => $fees,
                    'platform_commission' => $commission,
                    'restaurant_net' => round($paid - $fees - $commission, 2),
                    'unpaid_due' => round(max(0, (float) ($dues[$restaurant->id] ?? 0)), 2),
                ];
            })
            ->all();
    }

    /**
     * @param  Builder<Order>  $orders
     * @return array{registered: int, ordered: int, new: int, returning: int}
     */
    private function customers(Builder $orders, ?Carbon $start, ?Carbon $end): array
    {
        $orderingIds = (clone $orders)->whereNotNull('customer_id')->distinct()->pluck('customer_id');
        $newCustomers = Customer::query()
            ->whereIn('id', $orderingIds)
            ->when($start && $end, fn (Builder $query) => $query->whereBetween('created_at', [$start, $end]))
            ->count();
        $ordered = $orderingIds->count();

        return [
            'registered' => Customer::query()->count(),
            'ordered' => $ordered,
            'new' => $newCustomers,
            'returning' => max(0, $ordered - $newCustomers),
        ];
    }

    /**
     * @param  Builder<Order>  $orders
     * @return array{active_drivers: int, available_drivers: int, fees: float, average_minutes: int, on_the_way: int}
     */
    private function delivery(Builder $orders, float $fees, int $onTheWay): array
    {
        $driver = DB::connection()->getDriverName();
        $average = (clone $orders)
            ->where('status', 'DELIVERED')
            ->whereNotNull('delivered_at')
            ->avg(DB::raw($this->minuteExpression($driver)));

        return [
            'active_drivers' => DeliveryDriver::query()->where('is_active', true)->count(),
            'available_drivers' => DeliveryDriver::query()->where('availability_status', 'AVAILABLE')->count(),
            'fees' => $fees,
            'average_minutes' => (int) round((float) $average),
            'on_the_way' => $onTheWay,
        ];
    }

    /**
     * @return list<array{name: string, amount: float}>
     */
    private function expenses(?Carbon $start, ?Carbon $end): array
    {
        return $this->expensesQuery($start, $end)
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->selectRaw('expense_categories.name as category_name, COALESCE(SUM(expenses.amount), 0) as amount')
            ->groupBy('expense_categories.name')
            ->orderByDesc('amount')
            ->get()
            ->map(fn (Expense $expense): array => [
                'name' => (string) $expense->getAttribute('category_name'),
                'amount' => round((float) $expense->getAttribute('amount'), 2),
            ])
            ->all();
    }

    /**
     * @return Builder<Expense>
     */
    private function expensesQuery(?Carbon $start, ?Carbon $end): Builder
    {
        return Expense::query()->when(
            $start && $end,
            fn (Builder $query) => $query->whereBetween('expense_date', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]),
        );
    }

    /**
     * @return list<array{date: string, label: string, revenue: float, delivered: int, cancelled: int}>
     */
    private function daily(?Carbon $start, ?Carbon $end): array
    {
        $chartStart = ($start ?? now()->subDays(29))->copy()->startOfDay();
        $chartEnd = ($end ?? now())->copy()->startOfDay();

        if ($chartEnd->greaterThan(now()->startOfDay())) {
            $chartEnd = now()->copy()->startOfDay();
        }

        $driver = DB::connection()->getDriverName();
        $dateExpression = $driver === 'sqlite' ? 'date(created_at)' : 'DATE(created_at)';
        $rows = Order::query()
            ->whereBetween('created_at', [$chartStart, $chartEnd->copy()->endOfDay()])
            ->selectRaw($dateExpression.' as order_date')
            ->selectRaw("SUM(CASE WHEN status = 'DELIVERED' THEN 1 ELSE 0 END) as delivered_count")
            ->selectRaw("SUM(CASE WHEN status = 'DELIVERED' THEN total_amount ELSE 0 END) as revenue")
            ->selectRaw("SUM(CASE WHEN status IN ('CANCELLED', 'REJECTED', 'REFUNDED') THEN 1 ELSE 0 END) as cancelled_count")
            ->groupByRaw($dateExpression)
            ->get()
            ->keyBy(fn (Order $order): string => Carbon::parse($order->getAttribute('order_date'))->toDateString());

        $days = [];
        $cursor = $chartStart->copy();

        while ($cursor->lte($chartEnd)) {
            $key = $cursor->toDateString();
            $row = $rows->get($key);
            $days[] = [
                'date' => $key,
                'label' => $cursor->format('d/m'),
                'revenue' => round((float) ($row->revenue ?? 0), 2),
                'delivered' => (int) ($row->delivered_count ?? 0),
                'cancelled' => (int) ($row->cancelled_count ?? 0),
            ];
            $cursor->addDay();
        }

        return $days;
    }

    /**
     * @return list<array{name: string, restaurant: string, qty: int, revenue: float}>
     */
    private function topItems(?Carbon $start, ?Carbon $end): array
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('restaurants', 'restaurants.id', '=', 'orders.restaurant_id')
            ->where('orders.status', 'DELIVERED')
            ->whereNull('orders.deleted_at')
            ->whereNull('restaurants.deleted_at')
            ->when($start && $end, fn (Builder $query) => $query->whereBetween('orders.created_at', [$start, $end]))
            ->selectRaw('order_items.name as item_name, restaurants.name as restaurant_name, SUM(order_items.quantity) as qty, COALESCE(SUM(order_items.total_price), 0) as revenue')
            ->groupBy('order_items.name', 'restaurants.name')
            ->orderByDesc('qty')
            ->limit(10)
            ->get()
            ->map(fn (OrderItem $item): array => [
                'name' => (string) $item->getAttribute('item_name'),
                'restaurant' => (string) $item->getAttribute('restaurant_name'),
                'qty' => (int) $item->getAttribute('qty'),
                'revenue' => round((float) $item->getAttribute('revenue'), 2),
            ])
            ->all();
    }

    private function rate(int $part, int $total): float
    {
        return $total > 0 ? round(($part / $total) * 100, 1) : 0;
    }

    private function minuteExpression(string $driver): string
    {
        return match ($driver) {
            'sqlite' => '(julianday(delivered_at) - julianday(created_at)) * 1440',
            'pgsql' => 'EXTRACT(EPOCH FROM (delivered_at - created_at)) / 60',
            default => 'TIMESTAMPDIFF(MINUTE, created_at, delivered_at)',
        };
    }
}
