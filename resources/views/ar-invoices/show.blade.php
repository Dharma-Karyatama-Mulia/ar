@extends('layouts.admin')

@section('title', $invoice->invoice_no)
@section('breadcrumb', $invoice->invoice_no)

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h5>{{ $invoice->invoice_no }}</h5>
                        <span class="badge bg-{{ $invoice->status_color }}">{{ $invoice->status_label }}</span>
                    </div>
                    <table class="table table-borderless mb-0">
                        <tr><td class="text-muted" style="width:160px">Customer</td><td>{{ $invoice->customer->name }}</td></tr>
                        <tr><td class="text-muted">Ship-to</td><td>{{ $invoice->shipto?->name ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Gudang</td><td>{{ $invoice->warehouse?->name ?? '-' }}</td></tr>
                        <tr><td class="text-muted">PO Number</td><td>{{ $invoice->po_number ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Tanggal Invoice</td><td>{{ $invoice->invoice_date->format('d/m/Y') }}</td></tr>
                        <tr><td class="text-muted">Jatuh Tempo</td><td>{{ $invoice->due_date?->format('d/m/Y') ?? '-' }} @if($invoice->age > 0)<span class="badge bg-danger ms-1">Overdue {{ $invoice->age }} hari</span>@endif</td></tr>
                        <tr><td class="text-muted">No. Faktur Pajak</td><td>{{ $invoice->tax_invoice_number ?? '-' }}</td></tr>
                        <tr><td class="text-muted">Catatan</td><td>{{ $invoice->notes ?? '-' }}</td></tr>
                        @if($invoice->salesOrder)
                            <tr><td class="text-muted">Dari Sales Order</td><td>{{ $invoice->salesOrder->so_no }}</td></tr>
                        @endif
                        <tr><td class="text-muted">Dibuat oleh</td><td>{{ $invoice->createdBy?->name ?? '-' }}</td></tr>
                        @if($invoice->approved_by)
                            <tr><td class="text-muted">Diproses oleh</td><td>{{ $invoice->approvedBy?->name }} pada {{ $invoice->approved_at?->format('d/m/Y H:i') }}</td></tr>
                        @endif
                        <tr><td class="text-muted">Tercetak</td><td>{{ $invoice->printed ? 'Sudah' : 'Belum' }}</td></tr>
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
                                @foreach($invoice->lines as $line)
                                    <tr>
                                        <td>{{ $line->display_description }}</td>
                                        <td>{{ rtrim(rtrim(number_format($line->qty, 4), '0'), '.') }}</td>
                                        <td>{{ number_format($line->unit_price, 2) }}</td>
                                        <td>{{ number_format($line->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr><th colspan="3" class="text-end">Subtotal</th><th>{{ number_format($invoice->subtotal, 2) }}</th></tr>
                                <tr><th colspan="3" class="text-end">PPN</th><th>{{ number_format($invoice->ppn_amount, 2) }}</th></tr>
                                <tr><th colspan="3" class="text-end">Total</th><th>{{ number_format($invoice->total, 2) }}</th></tr>
                                <tr><th colspan="3" class="text-end">Sudah Dibayar</th><th>{{ number_format($invoice->paid_amount, 2) }}</th></tr>
                                @if($invoice->disc_taken_amount > 0)
                                    <tr><th colspan="3" class="text-end">Diskon Diambil</th><th>{{ number_format($invoice->disc_taken_amount, 2) }}</th></tr>
                                @endif
                                @if($invoice->write_off_amount > 0)
                                    <tr><th colspan="3" class="text-end">Write-off</th><th>{{ number_format($invoice->write_off_amount, 2) }}</th></tr>
                                @endif
                                @if($invoice->credited_amount > 0)
                                    <tr><th colspan="3" class="text-end">Dikreditkan (Credit Note)</th><th>{{ number_format($invoice->credited_amount, 2) }}</th></tr>
                                @endif
                                <tr><th colspan="3" class="text-end">Owing</th><th>{{ number_format($invoice->owing, 2) }}</th></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            @if($invoice->allocations->isNotEmpty())
                <div class="card mt-3">
                    <div class="card-body">
                        <h6>Riwayat Pembayaran</h6>
                        <ul class="list-unstyled mb-0">
                            @foreach($invoice->allocations as $allocation)
                                <li>
                                    <a href="{{ route('ar-payments.show', $allocation->payment) }}">{{ $allocation->payment->payment_no }}</a>
                                    — {{ number_format($allocation->amount, 2) }}
                                    @if($allocation->disc_taken_amount > 0) (diskon {{ number_format($allocation->disc_taken_amount, 2) }}) @endif
                                    @if($allocation->write_off_amount > 0) (write-off {{ number_format($allocation->write_off_amount, 2) }}) @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @if($invoice->creditNotes->isNotEmpty())
                <div class="card mt-3">
                    <div class="card-body">
                        <h6>Credit Note Terkait</h6>
                        <ul class="list-unstyled mb-0">
                            @foreach($invoice->creditNotes as $creditNote)
                                <li><a href="{{ route('ar-credit-notes.show', $creditNote) }}">{{ $creditNote->credit_note_no }}</a> — <span class="badge bg-{{ $creditNote->status_color }}">{{ $creditNote->status_label }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body d-flex flex-column gap-2">
                    <h6>Aksi</h6>

                    @if($invoice->status === 'draft')
                        @canWrite
                            <a href="{{ route('ar-invoices.edit', $invoice) }}" class="btn btn-outline-secondary">Edit</a>
                            <form method="POST" action="{{ route('ar-invoices.submit', $invoice) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary w-100">Ajukan untuk Approval</button>
                            </form>
                            <form method="POST" action="{{ route('ar-invoices.destroy', $invoice) }}" onsubmit="return confirm('Hapus invoice ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger w-100">Hapus</button>
                            </form>
                        @endcanWrite
                    @endif

                    @if($invoice->status === 'diajukan')
                        @canApprove
                            <form method="POST" action="{{ route('ar-invoices.approve', $invoice) }}">
                                @csrf
                                <button type="submit" class="btn btn-success w-100">Setujui</button>
                            </form>
                            <form method="POST" action="{{ route('ar-invoices.reject', $invoice) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger w-100">Tolak</button>
                            </form>
                        @endcanApprove
                    @endif

                    @if(in_array($invoice->status, ['disetujui', 'selesai']))
                        @canWrite
                            @unless($invoice->printed)
                                <form method="POST" action="{{ route('ar-invoices.mark-printed', $invoice) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary w-100">Tandai Sudah Dicetak</button>
                                </form>
                            @endunless
                            @if($invoice->owing > 0.009)
                                <a href="{{ route('ar-payments.create', ['customer_id' => $invoice->customer_id]) }}" class="btn btn-primary">Catat Pembayaran</a>
                                <a href="{{ route('ar-credit-notes.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-outline-primary">Buat Credit Note</a>
                            @endif
                        @endcanWrite
                    @endif

                    <a href="{{ route('ar-invoices.index') }}" class="btn btn-link">&larr; Kembali ke daftar</a>
                </div>
            </div>
        </div>
    </div>
@endsection
