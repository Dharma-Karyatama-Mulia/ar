@extends('layouts.admin')

@section('title', 'AR Invoices')
@section('breadcrumb', 'AR Invoices')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <form method="GET" class="d-flex gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Cari no. invoice..." style="width:220px">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
                </form>
                @canWrite
                    <a href="{{ route('ar-invoices.create') }}" class="btn btn-primary">
                        <i data-feather="plus"></i> Buat Invoice
                    </a>
                @endcanWrite
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No. Invoice</th>
                            <th>Customer</th>
                            <th>Tanggal</th>
                            <th>Jatuh Tempo</th>
                            <th>Total</th>
                            <th>Owing</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr style="cursor:pointer" onclick="window.location='{{ route('ar-invoices.show', $invoice) }}'">
                                <td>{{ $invoice->invoice_no }}</td>
                                <td>{{ $invoice->customer->name }}</td>
                                <td>{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                                <td>{{ $invoice->due_date?->format('d/m/Y') ?? '-' }}</td>
                                <td>{{ number_format($invoice->total, 2) }}</td>
                                <td>{{ number_format($invoice->owing, 2) }}</td>
                                <td><span class="badge bg-{{ $invoice->status_color }}">{{ $invoice->status_label }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">Belum ada invoice.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $invoices->links() }}
        </div>
    </div>
@endsection
