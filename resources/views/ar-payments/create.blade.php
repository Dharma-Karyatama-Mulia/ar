@extends('layouts.admin')

@section('title', 'Catat Pembayaran')
@section('breadcrumb', 'Catat Pembayaran')

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-select" onchange="this.form.submit()">
                        <option value="">- Pilih Customer -</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" @selected($selectedCustomerId === $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if($selectedCustomerId)
        <form method="POST" action="{{ route('ar-payments.store') }}">
            @csrf
            <input type="hidden" name="customer_id" value="{{ $selectedCustomerId }}">

            <div class="card mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Bank</label>
                            <select name="bank_id" class="form-select">
                                <option value="">- Pilih Bank -</option>
                                @foreach($banks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">No. Referensi / Cheque</label>
                            <input type="text" name="reference_no" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Pembayaran</label>
                            <input type="date" name="payment_date" class="form-control" required value="{{ now()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Jumlah Pembayaran (Total)</label>
                            <input type="number" step="0.01" id="payment-amount" name="amount" class="form-control" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h6>Alokasi ke Invoice Outstanding</h6>
                    <div class="table-responsive">
                        <table class="table" id="alloc-table">
                            <thead>
                                <tr>
                                    <th>No. Invoice</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Owing</th>
                                    <th style="width:14%">Jumlah Dibayar</th>
                                    <th style="width:12%">Diskon</th>
                                    <th style="width:12%">Write-off</th>
                                    <th style="width:16%">GL Write-off</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($outstandingInvoices as $invoice)
                                    <tr>
                                        <td>
                                            {{ $invoice->invoice_no }}
                                            <input type="hidden" name="allocations[{{ $loop->index }}][ar_invoice_id]" value="{{ $invoice->id }}">
                                        </td>
                                        <td>{{ $invoice->due_date?->format('d/m/Y') ?? '-' }} @if($invoice->age > 0)<span class="badge bg-danger">{{ $invoice->age }}h</span>@endif</td>
                                        <td class="owing-cell" data-owing="{{ $invoice->owing }}">{{ number_format($invoice->owing, 2) }}</td>
                                        <td><input type="number" step="0.01" min="0" class="form-control form-control-sm alloc-input" name="allocations[{{ $loop->index }}][amount]"></td>
                                        <td><input type="number" step="0.01" min="0" class="form-control form-control-sm alloc-input" name="allocations[{{ $loop->index }}][disc_taken_amount]"></td>
                                        <td><input type="number" step="0.01" min="0" class="form-control form-control-sm alloc-input" name="allocations[{{ $loop->index }}][write_off_amount]"></td>
                                        <td>
                                            <select name="allocations[{{ $loop->index }}][write_off_gl_account_id]" class="form-select form-select-sm">
                                                <option value="">-</option>
                                                @foreach($glAccounts as $glAccount)
                                                    <option value="{{ $glAccount->id }}">{{ $glAccount->code }} - {{ $glAccount->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted">Tidak ada invoice outstanding untuk customer ini.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Total Dialokasikan</th>
                                    <th id="total-allocated" colspan="4">0.00</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary" @disabled($outstandingInvoices->isEmpty())>Simpan Pembayaran</button>
                <a href="{{ route('ar-payments.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>

        @push('scripts')
        <script>
            function recalcTotal() {
                let total = 0;
                document.querySelectorAll('.alloc-input').forEach(function (input) {
                    total += parseFloat(input.value) || 0;
                });
                document.getElementById('total-allocated').textContent = total.toLocaleString('id-ID', {minimumFractionDigits: 2});
            }
            document.getElementById('alloc-table').addEventListener('input', recalcTotal);
        </script>
        @endpush
    @endif
@endsection
