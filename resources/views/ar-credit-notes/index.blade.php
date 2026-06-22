@extends('layouts.admin')

@section('title', 'Credit Notes')
@section('breadcrumb', 'Credit Notes')

@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Credit Notes</h5>
                @canWrite
                    <a href="{{ route('ar-credit-notes.create') }}" class="btn btn-primary">
                        <i data-feather="plus"></i> Buat Credit Note
                    </a>
                @endcanWrite
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No. Credit Note</th>
                            <th>Customer</th>
                            <th>Invoice Terkait</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($creditNotes as $creditNote)
                            <tr style="cursor:pointer" onclick="window.location='{{ route('ar-credit-notes.show', $creditNote) }}'">
                                <td>{{ $creditNote->credit_note_no }}</td>
                                <td>{{ $creditNote->customer->name }}</td>
                                <td>{{ $creditNote->invoice->invoice_no }}</td>
                                <td>{{ number_format($creditNote->total, 2) }}</td>
                                <td><span class="badge bg-{{ $creditNote->status_color }}">{{ $creditNote->status_label }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">Belum ada credit note.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $creditNotes->links() }}
        </div>
    </div>
@endsection
