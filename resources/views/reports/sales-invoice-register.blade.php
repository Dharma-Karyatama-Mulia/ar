@extends('layouts.admin')

@section('title', 'Sales Invoice Register')
@section('breadcrumb', 'Sales Invoice Register')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3">
                    <input type="date" name="date_from" class="form-control" value="{{ $dateFrom->format('Y-m-d') }}">
                </div>
                <div class="col-md-3">
                    <input type="date" name="date_to" class="form-control" value="{{ $dateTo->format('Y-m-d') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-secondary">Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>No. Invoice</th>
                            <th>Tanggal</th>
                            <th>Customer</th>
                            <th>No. PO</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">PPN</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td><a href="{{ route('ar-invoices.show', $invoice) }}">{{ $invoice->invoice_no }}</a></td>
                                <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                                <td>{{ $invoice->customer->name ?? '-' }}</td>
                                <td>{{ $invoice->po_number ?? '-' }}</td>
                                <td class="text-end">{{ number_format($invoice->subtotal, 2) }}</td>
                                <td class="text-end">{{ number_format($invoice->ppn_amount, 2) }}</td>
                                <td class="text-end">{{ number_format($invoice->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">Tidak ada invoice di rentang tanggal ini.</td></tr>
                        @endforelse
                    </tbody>
                    @if($invoices->isNotEmpty())
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="4" class="text-end">Grand Total</td>
                                <td class="text-end">{{ number_format($grandTotal['subtotal'], 2) }}</td>
                                <td class="text-end">{{ number_format($grandTotal['ppn_amount'], 2) }}</td>
                                <td class="text-end">{{ number_format($grandTotal['total'], 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
