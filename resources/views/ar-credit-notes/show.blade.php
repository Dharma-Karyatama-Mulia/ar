@extends('layouts.admin')

@section('title', $creditNote->credit_note_no)
@section('breadcrumb', $creditNote->credit_note_no)

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h5>{{ $creditNote->credit_note_no }}</h5>
                        <span class="badge bg-{{ $creditNote->status_color }}">{{ $creditNote->status_label }}</span>
                    </div>
                    <table class="table table-borderless mb-0">
                        <tr><td class="text-muted" style="width:160px">Customer</td><td>{{ $creditNote->customer->name }}</td></tr>
                        <tr><td class="text-muted">Invoice Terkait</td><td><a href="{{ route('ar-invoices.show', $creditNote->invoice) }}">{{ $creditNote->invoice->invoice_no }}</a></td></tr>
                        <tr><td class="text-muted">Alasan</td><td>{{ $creditNote->reason ?? '-' }}</td></tr>
                        <tr><td class="text-muted">No. Faktur Pajak</td><td>{{ $creditNote->tax_invoice_number ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Catatan</td><td>{{ $creditNote->notes ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Dibuat oleh</td><td>{{ $creditNote->createdBy?->name ?? '-' }}</td></tr>
                        @if($creditNote->approved_by)
                            <tr><td class="text-muted">Diproses oleh</td><td>{{ $creditNote->approvedBy?->name }} pada {{ $creditNote->approved_at?->format('d/m/Y H:i') }}</td></tr>
                        @endif
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <h6>Item</h6>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr><th>Item</th><th>Qty</th><th>Harga Satuan</th><th>Subtotal</th></tr>
                            </thead>
                            <tbody>
                                @foreach($creditNote->lines as $line)
                                    <tr>
                                        <td>{{ $line->display_description }}</td>
                                        <td>{{ rtrim(rtrim(number_format($line->qty, 4), '0'), '.') }}</td>
                                        <td>{{ number_format($line->unit_price, 2) }}</td>
                                        <td>{{ number_format($line->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr><th colspan="3" class="text-end">Total</th><th>{{ number_format($creditNote->total, 2) }}</th></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body d-flex flex-column gap-2">
                    <h6>Aksi</h6>

                    @if($creditNote->status === 'draft')
                        @canWrite
                            <form method="POST" action="{{ route('ar-credit-notes.submit', $creditNote) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100">Ajukan untuk Approval</button>
                            </form>
                            <form method="POST" action="{{ route('ar-credit-notes.destroy', $creditNote) }}" onsubmit="return confirm('Hapus credit note ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger w-100">Hapus</button>
                            </form>
                        @endcanWrite
                    @endif

                    @if($creditNote->status === 'diajukan')
                        @canApprove
                            <form method="POST" action="{{ route('ar-credit-notes.approve', $creditNote) }}">
                                @csrf
                                <button type="submit" class="btn btn-success w-100">Setujui</button>
                            </form>
                            <form method="POST" action="{{ route('ar-credit-notes.reject', $creditNote) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger w-100">Tolak</button>
                            </form>
                        @endcanApprove
                    @endif

                    <a href="{{ route('ar-credit-notes.index') }}" class="btn btn-link">&larr; Kembali ke daftar</a>
                </div>
            </div>
        </div>
    </div>
@endsection
