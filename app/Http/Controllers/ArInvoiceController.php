<?php

namespace App\Http\Controllers;

use App\Models\ArInvoice;
use App\Models\Customer;
use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\Shipto;
use App\Models\Warehouse;
use App\Services\AutoNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ArInvoiceController extends Controller
{
    private const PPN_RATE = 0.11;

    public function index(Request $request): View
    {
        $invoices = ArInvoice::with('customer')
            ->when($request->search, fn ($q, $search) => $q->where('invoice_no', 'like', "%{$search}%"))
            ->orderByDesc('invoice_date')
            ->paginate(20)
            ->withQueryString();

        return view('ar-invoices.index', compact('invoices'));
    }

    public function createPicker(): View
    {
        $eligibleSalesOrders = SalesOrder::with('customer')
            ->where('status', 'selesai')
            ->whereDoesntHave('arInvoice')
            ->orderByDesc('order_date')
            ->get();

        return view('ar-invoices.create-picker', compact('eligibleSalesOrders'));
    }

    public function createFromSalesOrder(SalesOrder $salesOrder): RedirectResponse
    {
        if ($salesOrder->status !== 'selesai') {
            return back()->with('error', 'Sales Order belum selesai (fully shipped).');
        }

        if (ArInvoice::where('sls_sales_order_id', $salesOrder->id)->exists()) {
            return back()->with('error', 'Sales Order ini sudah pernah ditagih.');
        }

        $invoice = DB::transaction(function () use ($salesOrder) {
            $invoice = ArInvoice::create([
                'invoice_no' => app(AutoNumberService::class)->generate('ar_invoice'),
                'customer_id' => $salesOrder->customer_id,
                'shipto_id' => $salesOrder->shipto_id,
                'warehouse_id' => $salesOrder->warehouse_id,
                'po_number' => $salesOrder->po_number,
                'sls_sales_order_id' => $salesOrder->id,
                'invoice_date' => now()->toDateString(),
                'term_days' => $salesOrder->customer->term_days ?? 0,
                'due_date' => now()->addDays($salesOrder->customer->term_days ?? 0)->toDateString(),
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            $invoice->lines()->createMany(
                $salesOrder->lines->map(fn ($line) => [
                    'item_id' => $line->item_id,
                    'qty' => $line->qty,
                    'unit_price' => $line->unit_price,
                    'item_override_description' => $line->item_override_description,
                ])->all()
            );

            $this->recalculateTax($invoice);

            return $invoice;
        });

        return redirect()->route('ar-invoices.edit', $invoice)->with('success', 'Invoice dibuat dari Sales Order '.$salesOrder->so_no.'. Silakan tinjau sebelum diajukan.');
    }

    public function create(): View
    {
        return view('ar-invoices.form', [
            'invoice' => new ArInvoice(),
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'shiptos' => Shipto::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'items' => Item::where('is_active', true)->where('is_sold', true)->orderBy('description')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $invoice = DB::transaction(function () use ($data) {
            $invoice = ArInvoice::create([
                'invoice_no' => app(AutoNumberService::class)->generate('ar_invoice'),
                'customer_id' => $data['customer_id'],
                'shipto_id' => $data['shipto_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'po_number' => $data['po_number'] ?? null,
                'invoice_date' => $data['invoice_date'],
                'term_days' => (int) ($data['term_days'] ?? 0),
                'due_date' => $data['due_date'] ?? \Carbon\Carbon::parse($data['invoice_date'])->addDays((int) ($data['term_days'] ?? 0))->toDateString(),
                'notes' => $data['notes'] ?? null,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            $invoice->lines()->createMany($data['lines']);
            $this->recalculateTax($invoice);

            return $invoice;
        });

        return redirect()->route('ar-invoices.show', $invoice)->with('success', 'Invoice berhasil dibuat.');
    }

    public function show(ArInvoice $invoice): View
    {
        $invoice->load(['customer', 'shipto', 'warehouse', 'salesOrder', 'lines.item', 'allocations.payment', 'creditNotes', 'createdBy', 'approvedBy']);

        return view('ar-invoices.show', compact('invoice'));
    }

    public function edit(ArInvoice $invoice): View
    {
        abort_if($invoice->status !== 'draft', 403, 'Hanya invoice draft yang bisa diedit.');

        return view('ar-invoices.form', [
            'invoice' => $invoice->load('lines'),
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'shiptos' => Shipto::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'items' => Item::where('is_active', true)->where('is_sold', true)->orderBy('description')->get(),
        ]);
    }

    public function update(Request $request, ArInvoice $invoice): RedirectResponse
    {
        abort_if($invoice->status !== 'draft', 403, 'Hanya invoice draft yang bisa diedit.');

        $data = $this->validateData($request);

        DB::transaction(function () use ($invoice, $data) {
            $invoice->update([
                'customer_id' => $data['customer_id'],
                'shipto_id' => $data['shipto_id'] ?? null,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'po_number' => $data['po_number'] ?? null,
                'invoice_date' => $data['invoice_date'],
                'term_days' => (int) ($data['term_days'] ?? 0),
                'due_date' => $data['due_date'] ?? \Carbon\Carbon::parse($data['invoice_date'])->addDays((int) ($data['term_days'] ?? 0))->toDateString(),
                'notes' => $data['notes'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            $invoice->lines()->delete();
            $invoice->lines()->createMany($data['lines']);
            $this->recalculateTax($invoice);
        });

        return redirect()->route('ar-invoices.show', $invoice)->with('success', 'Invoice berhasil diperbarui.');
    }

    public function destroy(ArInvoice $invoice): RedirectResponse
    {
        abort_if($invoice->status !== 'draft', 403, 'Hanya invoice draft yang bisa dihapus.');

        $invoice->delete();

        return redirect()->route('ar-invoices.index')->with('success', 'Invoice berhasil dihapus.');
    }

    public function submit(ArInvoice $invoice): RedirectResponse
    {
        abort_if($invoice->status !== 'draft', 403);

        $invoice->update(['status' => 'diajukan', 'updated_by' => Auth::id()]);

        return back()->with('success', 'Invoice diajukan untuk approval.');
    }

    public function approve(ArInvoice $invoice): RedirectResponse
    {
        abort_if($invoice->status !== 'diajukan', 403);

        $invoice->update(['status' => 'disetujui', 'approved_by' => Auth::id(), 'approved_at' => now()]);

        return back()->with('success', 'Invoice disetujui.');
    }

    public function reject(ArInvoice $invoice): RedirectResponse
    {
        abort_if($invoice->status !== 'diajukan', 403);

        $invoice->update(['status' => 'ditolak', 'approved_by' => Auth::id(), 'approved_at' => now()]);

        return back()->with('success', 'Invoice ditolak.');
    }

    public function markPrinted(ArInvoice $invoice): RedirectResponse
    {
        $invoice->update(['printed' => true, 'updated_by' => Auth::id()]);

        return back()->with('success', 'Invoice ditandai sudah dicetak.');
    }

    private function recalculateTax(ArInvoice $invoice): void
    {
        $invoice->load('lines');
        $subtotal = $invoice->subtotal;

        $invoice->update([
            'dpp_amount' => $subtotal,
            'ppn_amount' => round($subtotal * self::PPN_RATE, 2),
        ]);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'shipto_id' => 'nullable|exists:shiptos,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'po_number' => 'nullable|string|max:50',
            'invoice_date' => 'required|date',
            'term_days' => 'nullable|integer|min:0',
            'due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,id',
            'lines.*.qty' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.item_override_description' => 'nullable|string|max:255',
        ]);
    }
}
