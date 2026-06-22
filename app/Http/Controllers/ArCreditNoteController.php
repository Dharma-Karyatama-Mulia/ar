<?php

namespace App\Http\Controllers;

use App\Models\ArCreditNote;
use App\Models\ArInvoice;
use App\Models\Item;
use App\Services\AutoNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ArCreditNoteController extends Controller
{
    public function index(): View
    {
        $creditNotes = ArCreditNote::with('customer', 'invoice')->orderByDesc('created_at')->paginate(20);

        return view('ar-credit-notes.index', compact('creditNotes'));
    }

    public function create(Request $request): View
    {
        $invoices = ArInvoice::with('customer')
            ->whereIn('status', ['disetujui', 'selesai'])
            ->orderByDesc('invoice_date')
            ->get()
            ->filter(fn (ArInvoice $invoice) => $invoice->owing > 0.009)
            ->values();

        $selectedInvoice = null;
        if ($request->invoice_id) {
            $selectedInvoice = ArInvoice::with('lines.item')->find($request->invoice_id);
        }

        $items = Item::where('is_active', true)->where('is_sold', true)->orderBy('description')->get();

        return view('ar-credit-notes.create', compact('invoices', 'selectedInvoice', 'items'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ar_invoice_id' => 'required|exists:ar_invoices,id',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'tax_invoice_number' => 'nullable|string|max:50',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,id',
            'lines.*.qty' => 'required|numeric|min:0.01',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.item_override_description' => 'nullable|string|max:255',
        ]);

        $invoice = ArInvoice::findOrFail($data['ar_invoice_id']);
        $creditTotal = collect($data['lines'])->sum(fn ($l) => $l['qty'] * $l['unit_price']);

        if ($creditTotal > $invoice->owing + 0.01) {
            return back()->withInput()->with('error', 'Total credit note tidak boleh melebihi sisa tagihan invoice ('.number_format($invoice->owing, 2).').');
        }

        $creditNote = DB::transaction(function () use ($data) {
            $creditNote = ArCreditNote::create([
                'credit_note_no' => app(AutoNumberService::class)->generate('ar_credit_note'),
                'ar_invoice_id' => $data['ar_invoice_id'],
                'customer_id' => ArInvoice::find($data['ar_invoice_id'])->customer_id,
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'tax_invoice_number' => $data['tax_invoice_number'] ?? null,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            $creditNote->lines()->createMany($data['lines']);

            return $creditNote;
        });

        return redirect()->route('ar-credit-notes.show', $creditNote)->with('success', 'Credit note berhasil dibuat.');
    }

    public function show(ArCreditNote $creditNote): View
    {
        $creditNote->load(['customer', 'invoice', 'lines.item', 'createdBy', 'approvedBy']);

        return view('ar-credit-notes.show', compact('creditNote'));
    }

    public function submit(ArCreditNote $creditNote): RedirectResponse
    {
        abort_if($creditNote->status !== 'draft', 403);

        $creditNote->update(['status' => 'diajukan', 'updated_by' => Auth::id()]);

        return back()->with('success', 'Credit note diajukan untuk approval.');
    }

    public function approve(ArCreditNote $creditNote): RedirectResponse
    {
        abort_if($creditNote->status !== 'diajukan', 403);

        if ($creditNote->total > $creditNote->invoice->owing + 0.01) {
            return back()->with('error', 'Sisa tagihan invoice sudah tidak cukup untuk credit note ini (mungkin sudah dibayar/dikreditkan oleh transaksi lain).');
        }

        $creditNote->update(['status' => 'disetujui', 'approved_by' => Auth::id(), 'approved_at' => now()]);

        return back()->with('success', 'Credit note disetujui.');
    }

    public function reject(ArCreditNote $creditNote): RedirectResponse
    {
        abort_if($creditNote->status !== 'diajukan', 403);

        $creditNote->update(['status' => 'ditolak', 'approved_by' => Auth::id(), 'approved_at' => now()]);

        return back()->with('success', 'Credit note ditolak.');
    }

    public function destroy(ArCreditNote $creditNote): RedirectResponse
    {
        abort_if($creditNote->status !== 'draft', 403, 'Hanya credit note draft yang bisa dihapus.');

        $creditNote->delete();

        return redirect()->route('ar-credit-notes.index')->with('success', 'Credit note berhasil dihapus.');
    }
}
