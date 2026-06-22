<?php

namespace App\Http\Controllers;

use App\Models\ArInvoice;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function agedReceivables(): View
    {
        $invoices = ArInvoice::with('customer', 'lines')
            ->whereIn('status', ['disetujui', 'selesai'])
            ->get()
            ->filter(fn (ArInvoice $invoice) => $invoice->owing > 0.009);

        $byCustomer = $invoices->groupBy('customer_id')->map(function ($group) {
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
                'invoices' => $group->values(),
                'buckets' => $buckets,
                'total' => array_sum($buckets),
            ];
        })->sortByDesc('total')->values();

        $grandTotal = [
            'current' => $byCustomer->sum('buckets.current'),
            'd30' => $byCustomer->sum('buckets.d30'),
            'd60' => $byCustomer->sum('buckets.d60'),
            'd90' => $byCustomer->sum('buckets.d90'),
            'd90plus' => $byCustomer->sum('buckets.d90plus'),
        ];

        return view('reports.aged-receivables', compact('byCustomer', 'grandTotal'));
    }
}
