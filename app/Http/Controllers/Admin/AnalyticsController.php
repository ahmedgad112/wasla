<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function index(): Response
    {
        $since = now()->subDays(29)->startOfDay();
        $driver = DB::connection()->getDriverName();

        $dailyRows = Order::query()
            ->where('created_at', '>=', $since)
            ->selectRaw($this->dateExpression($driver).' as order_date')
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw("SUM(CASE WHEN status = 'DELIVERED' THEN total_amount ELSE 0 END) as revenue")
            ->groupByRaw($this->dateExpression($driver))
            ->get()
            ->keyBy(fn (Order $order): string => Carbon::parse($order->getAttribute('order_date'))->toDateString());

        $ordersByDay = collect(range(0, 29))->map(function (int $offset) use ($since, $dailyRows): array {
            $date = $since->copy()->addDays($offset);
            $row = $dailyRows->get($date->toDateString());

            return [
                'day' => $date->format('d/m'),
                'count' => (int) ($row->order_count ?? 0),
                'revenue' => round((float) ($row->revenue ?? 0), 2),
            ];
        })->all();

        $hourExpression = $this->hourExpression($driver);
        $countsByHour = Order::query()
            ->where('created_at', '>=', $since)
            ->selectRaw($hourExpression.' as order_hour, COUNT(*) as order_count')
            ->groupByRaw($hourExpression)
            ->get()
            ->mapWithKeys(fn (Order $order): array => [
                (int) $order->getAttribute('order_hour') => (int) $order->order_count,
            ]);

        $ordersByHour = collect(range(0, 23))->map(fn (int $hour): array => [
            'hour' => $hour,
            'count' => (int) ($countsByHour[$hour] ?? 0),
        ])->all();

        $topRestaurants = Restaurant::query()
            ->whereHas('orders', fn ($query) => $query->where('created_at', '>=', $since))
            ->withCount([
                'orders as orders_count' => fn ($query) => $query->where('created_at', '>=', $since),
            ])
            ->withSum([
                'orders as revenue_sum' => fn ($query) => $query
                    ->where('status', 'DELIVERED')
                    ->where('created_at', '>=', $since),
            ], 'total_amount')
            ->orderByDesc('orders_count')
            ->limit(10)
            ->get()
            ->map(fn (Restaurant $restaurant): array => [
                'name' => $restaurant->name,
                'orders' => (int) $restaurant->orders_count,
                'revenue' => round((float) $restaurant->revenue_sum, 2),
            ])
            ->all();

        $topMenuItems = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('restaurants', 'restaurants.id', '=', 'orders.restaurant_id')
            ->where('orders.created_at', '>=', $since)
            ->whereNull('orders.deleted_at')
            ->whereNull('restaurants.deleted_at')
            ->selectRaw('order_items.name as item_name, restaurants.name as restaurant_name, SUM(order_items.quantity) as item_count')
            ->groupBy('order_items.name', 'restaurants.name')
            ->orderByRaw('SUM(order_items.quantity) DESC')
            ->limit(10)
            ->get()
            ->map(fn (OrderItem $item): array => [
                'name' => (string) $item->getAttribute('item_name'),
                'restaurant' => (string) $item->getAttribute('restaurant_name'),
                'count' => (int) $item->getAttribute('item_count'),
            ])
            ->all();

        $orderingCustomerIds = Order::query()
            ->where('created_at', '>=', $since)
            ->distinct()
            ->pluck('customer_id');

        $newCustomers = Customer::query()
            ->whereIn('id', $orderingCustomerIds)
            ->where('created_at', '>=', $since)
            ->count();

        $returningCustomers = Customer::query()
            ->whereIn('id', $orderingCustomerIds)
            ->where('created_at', '<', $since)
            ->count();

        $avgDeliveryTime = (int) round((float) Order::query()
            ->where('status', 'DELIVERED')
            ->where('created_at', '>=', $since)
            ->whereNotNull('delivered_at')
            ->avg(DB::raw($this->minuteExpression($driver))));

        return Inertia::render('Admin/Analytics/Index', [
            'ordersByHour' => $ordersByHour,
            'ordersByDay' => $ordersByDay,
            'topRestaurants' => $topRestaurants,
            'topMenuItems' => $topMenuItems,
            'customerRetention' => [
                'new_customers' => $newCustomers,
                'returning_customers' => $returningCustomers,
            ],
            'avgDeliveryTime' => $avgDeliveryTime,
            'summaryStats' => [
                'total_orders_30d' => (int) collect($ordersByDay)->sum('count'),
                'revenue_30d' => round((float) collect($ordersByDay)->sum('revenue'), 2),
                'new_customers_30d' => Customer::query()->where('created_at', '>=', $since)->count(),
                'avg_rating' => 0,
            ],
        ]);
    }

    private function dateExpression(string $driver): string
    {
        return $driver === 'sqlite' ? 'date(created_at)' : 'DATE(created_at)';
    }

    private function hourExpression(string $driver): string
    {
        return match ($driver) {
            'sqlite' => "cast(strftime('%H', created_at) as integer)",
            'pgsql' => 'EXTRACT(HOUR FROM created_at)',
            default => 'HOUR(created_at)',
        };
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
