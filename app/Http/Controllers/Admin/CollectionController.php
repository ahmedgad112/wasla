<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Collection;
use App\Models\Invoice;
use App\Services\FinancialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function __construct(protected FinancialService $financialService) {}

    public function index(Request $request): Response
    {
        $restaurantTable = $this->financialService->getRestaurantFinancialTable();
        $collections = Collection::with(['restaurant:id,name', 'collectedByUser:id,name'])
            ->latest('collection_date')
            ->paginate(15);

        return Inertia::render('Admin/Collections/Index', [
            'restaurant_table' => $restaurantTable,
            'collections' => $collections,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'collection_date' => 'required|date',
        ]);

        if (! empty($validated['invoice_id'])) {
            $belongs = Invoice::query()
                ->whereKey($validated['invoice_id'])
                ->where('restaurant_id', $validated['restaurant_id'])
                ->exists();

            if (! $belongs) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'الفاتورة لا تتبع المطعم المحدد.',
                ]);
            }
        }

        $validated['collected_by_user_id'] = auth()->id();

        $collection = Collection::create($validated);

        // Update invoice if linked
        if ($collection->invoice_id) {
            $invoice = $collection->invoice;
            if ($invoice) {
                $totalPaid = $invoice->collections()->sum('amount');
                $status = $totalPaid >= $invoice->total_amount ? 'PAID'
                    : ($totalPaid > 0 ? 'PARTIALLY_PAID' : $invoice->status);
                $invoice->update(['paid_amount' => $totalPaid, 'status' => $status]);
            }
        }

        ActivityLog::log('COLLECTION_RECORDED', 'Collection', $collection->id, null, [
            'restaurant_id' => $validated['restaurant_id'],
            'amount' => $validated['amount'],
        ]);

        return back()->with('success', 'تم تسجيل التحصيل بنجاح.');
    }

    public function show(int $id): Response
    {
        $collection = Collection::with(['restaurant', 'invoice'])->findOrFail($id);

        return Inertia::render('Admin/Collections/Show', ['collection' => $collection]);
    }
}
