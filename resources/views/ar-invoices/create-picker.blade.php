@extends('layouts.admin')

@section('title', 'Buat Invoice')
@section('breadcrumb', 'Buat Invoice')

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <h6 class="card-title">Opsi 1 — Dari Sales Order (sls) yang sudah selesai</h6>
            <p class="text-muted small">Customer, ship-to, dan baris item akan otomatis terisi dari Sales Order yang dipilih.</p>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No. SO</th>
                            <th>Customer</th>
                            <th>Tanggal</th>
                            <th>PO Number</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($eligibleSalesOrders as $so)
                            <tr>
                                <td>{{ $so->so_no }}</td>
                                <td>{{ $so->customer->name }}</td>
                                <td>{{ $so->order_date->format('d/m/Y') }}</td>
                                <td>{{ $so->po_number ?? '-' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('ar-invoices.create-from-so', $so) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">Buat Invoice</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Tidak ada Sales Order yang siap ditagih.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="card-title">Opsi 2 — Manual (tanpa Sales Order)</h6>
            <p class="text-muted small">Untuk billing jasa atau lain-lain yang tidak berasal dari Sales Order sls.</p>
            <a href="{{ route('ar-invoices.create-manual') }}" class="btn btn-outline-primary">Buat Invoice Manual</a>
        </div>
    </div>
@endsection
