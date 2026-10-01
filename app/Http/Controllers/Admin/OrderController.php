<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService) {}

    public function index(Request $request): Response
    {
        $query = Order::with(['customer.user', 'restaurant:id,name', 'deliveryDriver:id,name'])
            ->latest();

        if ($request->string('archive')->toString() === 'archived') {
            $query->archived();
        } else {
            $query->notArchived();
        }

        if ($request->filled('search')) {
            $query->where('order_number', 'like', "%{$request->search}%");
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('restaurant_id')) {
            $query->where('restaurant_id', $request->restaurant_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only('search', 'status', 'restaurant_id', 'date_from', 'date_to', 'archive'),
        ]);
    }

    public function show(int $id): Response
    {
        $order = Order::with([
            'customer.user',
            'restaurant',
            'items',
            'deliveryDriver.user',
            'statusHistories' => fn ($q) => $q->orderBy('created_at'),
        ])->findOrFail($id);

        return Inertia::render('Admin/Orders/Show', [
            'order' => $order,
        ]);
    }

    public function archive(int $id): RedirectResponse
    {
        $this->markArchived($this->ordersFromIds([$id]));

        return back()->with('success', 'تم أرشفة الطلب.');
    }

    public function restore(int $id): RedirectResponse
    {
        $this->markRestored($this->ordersFromIds([$id]));

        return back()->with('success', 'تمت استعادة الطلب إلى القائمة النشطة.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->deleteOrders($this->ordersFromIds([$id]));

        return back()->with('success', 'تم حذف الطلب.');
    }

    public function bulkArchive(Request $request): RedirectResponse
    {
        $this->markArchived($this->ordersFromRequest($request));

        return back()->with('success', 'تم أرشفة الطلبات المحددة.');
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $this->markRestored($this->ordersFromRequest($request));

        return back()->with('success', 'تمت استعادة الطلبات المحددة.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $this->deleteOrders($this->ordersFromRequest($request));

        return back()->with('success', 'تم حذف الطلبات المحددة.');
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, Order>
     */
    private function ordersFromIds(array $ids): Collection
    {
        $orders = Order::query()->whereIn('id', $ids)->get();

        abort_if($orders->isEmpty(), 404);

        return $orders;
    }

    /**
     * @return Collection<int, Order>
     */
    private function ordersFromRequest(Request $request): Collection
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:orders,id'],
        ], [
            'ids.required' => 'اختر طلباً واحداً على الأقل.',
            'ids.min' => 'اختر طلباً واحداً على الأقل.',
        ]);

        return $this->ordersFromIds($validated['ids']);
    }

    /**
     * @param  Collection<int, Order>  $orders
     */
    private function markArchived(Collection $orders): void
    {
        Order::query()->whereIn('id', $orders->modelKeys())->update(['archived_at' => now()]);

        foreach ($orders as $order) {
            ActivityLog::log('ORDER_ARCHIVED', 'Order', $order->id, null, [
                'order_number' => $order->order_number,
            ]);
        }
    }

    /**
     * @param  Collection<int, Order>  $orders
     */
    private function markRestored(Collection $orders): void
    {
        Order::query()->whereIn('id', $orders->modelKeys())->update(['archived_at' => null]);

        foreach ($orders as $order) {
            ActivityLog::log('ORDER_RESTORED', 'Order', $order->id, null, [
                'order_number' => $order->order_number,
            ]);
        }
    }

    /**
     * @param  Collection<int, Order>  $orders
     */
    private function deleteOrders(Collection $orders): void
    {
        foreach ($orders as $order) {
            ActivityLog::log('ORDER_DELETED', 'Order', $order->id, null, [
                'order_number' => $order->order_number,
            ]);
            $order->delete();
        }
    }
}
