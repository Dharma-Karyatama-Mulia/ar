<?php

namespace App\Http\Controllers;

use App\Models\ArCreditNote;
use App\Models\ArInvoice;
use App\Models\ArPayment;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $openInvoices = ArInvoice::with('lines')
            ->whereIn('status', ['disetujui', 'selesai'])
            ->get()
            ->filter(fn (ArInvoice $invoice) => $invoice->owing > 0.009);

        return view('dashboard', [
            'invoiceDraftCount' => ArInvoice::whereIn('status', ['draft', 'diajukan'])->count(),
            'paymentDraftCount' => ArPayment::whereIn('status', ['draft', 'diajukan'])->count(),
            'creditNoteDraftCount' => ArCreditNote::whereIn('status', ['draft', 'diajukan'])->count(),
            'totalOutstanding' => $openInvoices->sum('owing'),
            'overdueCount' => $openInvoices->filter(fn (ArInvoice $invoice) => $invoice->age > 0)->count(),
        ]);
    }
}
