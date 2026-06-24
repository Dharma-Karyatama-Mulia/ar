<?php

namespace App\Http\Controllers;

use App\Models\ArInvoice;
use App\Models\ArInvoiceLine;
use App\Models\ArPayment;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Item;
use App\Models\ItemType;
use App\Models\Salesman;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Konsolidasi 8 varian "Sales by ..." BS1 (semuanya cuma beda dimensi
     * group-by) jadi satu report dengan selector, daripada 8 method/view terpisah.
     */
    private const SALES_ANALYSIS_DIMS = [
        'customer' => ['customer'],
        'customer_item_type_item' => ['customer', 'item_type', 'item'],
        'customer_type' => ['customer_type'],
        'customer_type_customer' => ['customer_type', 'customer'],
        'salesman' => ['salesman'],
        'item_type' => ['item_type'],
        'item_type_item' => ['item_type', 'item'],
        'item_type_item_customer' => ['item_type', 'item', 'customer'],
    ];

    private const SALES_ANALYSIS_DIM_KEYS = [
        'customer' => ['key' => 'customer_id', 'label' => 'customer_name'],
        'customer_type' => ['key' => 'customer_type_id', 'label' => 'customer_type_name'],
        'salesman' => ['key' => 'salesman_id', 'label' => 'salesman_name'],
        'item_type' => ['key' => 'item_type_id', 'label' => 'item_type_name'],
        'item' => ['key' => 'item_id', 'label' => 'item_label'],
    ];

    public function salesAnalysisLabels(): array
    {
        return [
            'customer' => 'Customer',
            'customer_item_type_item' => 'Customer / Type / Item',
            'customer_type' => 'Customer Type',
            'customer_type_customer' => 'Customer Type / Customer',
            'salesman' => 'Salesman',
            'item_type' => 'Type',
            'item_type_item' => 'Type / Item',
            'item_type_item_customer' => 'Type / Item / Customer',
        ];
    }

    public function salesAnalysis(Request $request): View
    {
        $groupBy = array_key_exists($request->input('group_by'), self::SALES_ANALYSIS_DIMS)
            ? $request->input('group_by')
            : 'customer';
        $dims = self::SALES_ANALYSIS_DIMS[$groupBy];

        $dateFrom = $request->date('date_from') ?? now()->startOfMonth();
        $dateTo = $request->date('date_to') ?? now()->endOfMonth();

        $lines = ArInvoiceLine::with(['invoice.customer.customerType', 'invoice.customer.salesman', 'item.itemType'])
            ->whereHas('invoice', fn ($q) => $q->whereIn('status', ['disetujui', 'selesai'])
                ->whereBetween('invoice_date', [$dateFrom, $dateTo]))
            ->get();

        $rows = $lines->map(function (ArInvoiceLine $line) {
            $invoice = $line->invoice;
            $customer = $invoice->customer;
            $item = $line->item;

            return [
                'invoice_no' => $invoice->invoice_no,
                'invoice_date' => $invoice->invoice_date,
                'customer_id' => $customer?->id,
                'customer_name' => $customer?->name ?? '(Tanpa Customer)',
                'customer_type_id' => $customer?->customer_type_id,
                'customer_type_name' => $customer?->customerType?->name ?? '(Tanpa Tipe Customer)',
                'salesman_id' => $customer?->salesman_id,
                'salesman_name' => $customer?->salesman?->name ?? '(Tanpa Salesman)',
                'item_type_id' => $item?->item_type_id,
                'item_type_name' => $item?->itemType?->name ?? '(Tanpa Tipe Item)',
                'item_id' => $line->item_id,
                'item_label' => $item ? "{$item->item_no} - {$item->description}" : '(Item Dihapus)',
                'qty' => (float) $line->qty,
                'amount' => $line->amount,
            ];
        })
            ->when($request->filled('salesman_id'), fn (Collection $r) => $r->where('salesman_id', (int) $request->input('salesman_id')))
            ->when($request->filled('customer_id'), fn (Collection $r) => $r->where('customer_id', (int) $request->input('customer_id')))
            ->when($request->filled('item_type_id'), fn (Collection $r) => $r->where('item_type_id', (int) $request->input('item_type_id')))
            ->when($request->filled('item_id'), fn (Collection $r) => $r->where('item_id', (int) $request->input('item_id')))
            ->values();

        $groups = $this->groupSalesRows($rows, $dims);
        $grandTotal = ['qty' => $rows->sum('qty'), 'amount' => $rows->sum('amount')];

        return view('reports.sales-analysis', [
            'groups' => $groups,
            'grandTotal' => $grandTotal,
            'groupBy' => $groupBy,
            'groupByLabels' => $this->salesAnalysisLabels(),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'salesmen' => Salesman::orderBy('name')->get(),
            'customers' => Customer::orderBy('name')->get(),
            'itemTypes' => ItemType::orderBy('name')->get(),
            'items' => Item::orderBy('item_no')->get(),
        ]);
    }

    private function groupSalesRows(Collection $rows, array $dims): Collection
    {
        $dim = $dims[0];
        $config = self::SALES_ANALYSIS_DIM_KEYS[$dim];
        $remaining = array_slice($dims, 1);

        return $rows->groupBy($config['key'])->map(function (Collection $group) use ($config, $remaining) {
            $node = [
                'label' => $group->first()[$config['label']],
                'qty' => $group->sum('qty'),
                'amount' => $group->sum('amount'),
            ];

            if (empty($remaining)) {
                $node['rows'] = $group->sortBy('invoice_date')->values();
            } else {
                $node['children'] = $this->groupSalesRows($group, $remaining);
            }

            return $node;
        })->sortBy('label')->values();
    }

    public function salesInvoiceRegister(Request $request): View
    {
        $dateFrom = $request->date('date_from') ?? now()->startOfMonth();
        $dateTo = $request->date('date_to') ?? now()->endOfMonth();

        $invoices = ArInvoice::with('customer')
            ->whereIn('status', ['disetujui', 'selesai'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->orderBy('invoice_date')
            ->orderBy('invoice_no')
            ->get();

        $grandTotal = [
            'subtotal' => $invoices->sum('subtotal'),
            'ppn_amount' => $invoices->sum('ppn_amount'),
            'total' => $invoices->sum('total'),
        ];

        return view('reports.sales-invoice-register', compact('invoices', 'grandTotal', 'dateFrom', 'dateTo'));
    }
    public function openReceivables(): View
    {
        $invoices = ArInvoice::with('customer')
            ->whereIn('status', ['disetujui', 'selesai'])
            ->get()
            ->filter(fn (ArInvoice $invoice) => $invoice->owing > 0.009);

        $byCustomer = $invoices->groupBy('customer_id')->map(fn ($group) => [
            'customer' => $group->first()->customer,
            'invoices' => $group->sortBy('due_date')->values(),
            'total' => $group->sum('owing'),
        ])->sortBy(fn ($row) => $row['customer']->name)->values();

        $grandTotal = $byCustomer->sum('total');

        return view('reports.open-receivables', compact('byCustomer', 'grandTotal'));
    }

    public function agedReceivables(): View
    {
        $byCustomer = $this->buildAgedReceivables();
        $grandTotal = $this->bucketGrandTotal($byCustomer);

        return view('reports.aged-receivables', compact('byCustomer', 'grandTotal'));
    }

    public function agedReceivablesSummary(): View
    {
        $byCustomer = $this->buildAgedReceivables();
        $grandTotal = $this->bucketGrandTotal($byCustomer);

        return view('reports.aged-receivables-summary', compact('byCustomer', 'grandTotal'));
    }

    /**
     * Dipakai bersama oleh agedReceivables() (detail per invoice) dan
     * agedReceivablesSummary() (rollup per customer saja) — query dan alokasi
     * bucket-nya identik, cuma view yang beda level detailnya.
     */
    private function buildAgedReceivables()
    {
        $invoices = ArInvoice::with('customer', 'lines')
            ->whereIn('status', ['disetujui', 'selesai'])
            ->get()
            ->filter(fn (ArInvoice $invoice) => $invoice->owing > 0.009);

        return $invoices->groupBy('customer_id')->map(function ($group) {
            $buckets = ['current' => 0, 'd30' => 0, 'd60' => 0, 'd90' => 0, 'd90plus' => 0];

            foreach ($group as $invoice) {
                $owing = $invoice->owing;
                $age = $invoice->age;

                match (true) {
                    $age === 0 => $buckets['current'] += $owing,
                    $age <= 30 => $buckets['d30'] += $owing,
                    $age <= 60 => $buckets['d60'] += $owing,
                    $age <= 90 => $buckets['d90'] += $owing,
                    default => $buckets['d90plus'] += $owing,
                };
            }

            return [
                'customer' => $group->first()->customer,
                'invoices' => $group->sortBy('due_date')->values(),
                'buckets' => $buckets,
                'total' => array_sum($buckets),
            ];
        })->sortByDesc('total')->values();
    }

    private function bucketGrandTotal($byCustomer): array
    {
        return [
            'current' => $byCustomer->sum('buckets.current'),
            'd30' => $byCustomer->sum('buckets.d30'),
            'd60' => $byCustomer->sum('buckets.d60'),
            'd90' => $byCustomer->sum('buckets.d90'),
            'd90plus' => $byCustomer->sum('buckets.d90plus'),
        ];
    }

    public function arHistory(Request $request): View
    {
        $dateFrom = $request->date('date_from') ?? now()->startOfMonth();
        $dateTo = $request->date('date_to') ?? now()->endOfMonth();

        $invoiceRows = ArInvoice::with('customer')
            ->whereIn('status', ['disetujui', 'selesai'])
            ->whereBetween('invoice_date', [$dateFrom, $dateTo])
            ->get()
            ->map(fn (ArInvoice $i) => [
                'customer_id' => $i->customer_id,
                'customer' => $i->customer,
                'type' => 'invoice',
                'no' => $i->invoice_no,
                'date' => $i->invoice_date,
                'amount' => $i->total,
            ]);

        $paymentRows = ArPayment::with('customer')
            ->whereIn('status', ['disetujui', 'selesai'])
            ->whereBetween('payment_date', [$dateFrom, $dateTo])
            ->get()
            ->map(fn (ArPayment $p) => [
                'customer_id' => $p->customer_id,
                'customer' => $p->customer,
                'type' => 'payment',
                'no' => $p->payment_no,
                'date' => $p->payment_date,
                'amount' => $p->amount,
            ]);

        $rows = $invoiceRows->concat($paymentRows);

        $byCustomer = $rows->groupBy('customer_id')->map(fn ($group) => [
            'customer' => $group->first()['customer'],
            'rows' => $group->sortBy('date')->values(),
        ])->sortBy(fn ($row) => $row['customer']->name)->values();

        $summary = [
            'invoice_count' => $invoiceRows->count(),
            'invoice_total' => $invoiceRows->sum('amount'),
            'payment_count' => $paymentRows->count(),
            'payment_total' => $paymentRows->sum('amount'),
        ];

        return view('reports.ar-history', compact('byCustomer', 'summary', 'dateFrom', 'dateTo'));
    }
}
