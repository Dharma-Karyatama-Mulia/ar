@extends('layouts.admin')

@section('title', 'Buat Credit Note')
@section('breadcrumb', 'Buat Credit Note')

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Invoice</label>
                    <select name="invoice_id" class="form-select" onchange="this.form.submit()">
                        <option value="">- Pilih Invoice -</option>
                        @foreach($invoices as $invoice)
                            <option value="{{ $invoice->id }}" @selected($selectedInvoice?->id === $invoice->id)>
                                {{ $invoice->invoice_no }} — {{ $invoice->customer->name }} (Owing: {{ number_format($invoice->owing, 2) }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if($selectedInvoice)
        <form method="POST" action="{{ route('ar-credit-notes.store') }}">
            @csrf
            <input type="hidden" name="ar_invoice_id" value="{{ $selectedInvoice->id }}">

            <div class="card mb-3">
                <div class="card-body">
                    <p class="mb-2">Invoice <strong>{{ $selectedInvoice->invoice_no }}</strong> — Owing saat ini: <strong>{{ number_format($selectedInvoice->owing, 2) }}</strong></p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Alasan</label>
                            <input type="text" name="reason" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No. Faktur Pajak Pengganti/Batal</label>
                            <input type="text" name="tax_invoice_number" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h6>Item yang Dikreditkan</h6>
                    <p class="text-muted small">Baris di bawah sudah terisi dari invoice asal — sesuaikan qty/harga sesuai jumlah yang benar-benar dikreditkan.</p>
                    <table class="table" id="line-table">
                        <thead>
                            <tr>
                                <th style="width:25%">Item</th>
                                <th style="width:12%">Qty</th>
                                <th style="width:15%">Harga Satuan</th>
                                <th style="width:15%">Subtotal</th>
                                <th style="width:23%">Override Desc.</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($selectedInvoice->lines as $line)
                                <tr class="line-row">
                                    <td>
                                        <select name="lines[][item_id]" class="form-select item-select" required>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}" data-price="{{ $item->sales_price }}" @selected($line->item_id === $item->id)>{{ $item->item_no }} - {{ $item->description }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.01" name="lines[][qty]" class="form-control qty-input" value="{{ $line->qty }}" required></td>
                                    <td><input type="number" step="0.01" name="lines[][unit_price]" class="form-control price-input" value="{{ $line->unit_price }}" required></td>
                                    <td class="line-subtotal align-middle">{{ number_format($line->amount, 2) }}</td>
                                    <td><input type="text" name="lines[][item_override_description]" class="form-control" value="{{ $line->item_override_description }}"></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i data-feather="trash-2"></i></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button type="button" id="add-row" class="btn btn-sm btn-outline-secondary mb-3">
                        <i data-feather="plus"></i> Tambah Baris
                    </button>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Simpan Credit Note</button>
                <a href="{{ route('ar-credit-notes.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>

        <template id="line-row-template">
            <tr class="line-row">
                <td>
                    <select name="lines[][item_id]" class="form-select item-select" required>
                        <option value="">- Pilih Item -</option>
                        @foreach($items as $item)
                            <option value="{{ $item->id }}" data-price="{{ $item->sales_price }}">{{ $item->item_no }} - {{ $item->description }}</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="number" step="0.01" name="lines[][qty]" class="form-control qty-input" value="1" required></td>
                <td><input type="number" step="0.01" name="lines[][unit_price]" class="form-control price-input" value="0" required></td>
                <td class="line-subtotal align-middle">0</td>
                <td><input type="text" name="lines[][item_override_description]" class="form-control"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i data-feather="trash-2"></i></button></td>
            </tr>
        </template>

        @push('scripts')
        <script>
            function recalcRow(row) {
                const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
                const price = parseFloat(row.querySelector('.price-input').value) || 0;
                row.querySelector('.line-subtotal').textContent = (qty * price).toLocaleString('id-ID', {minimumFractionDigits: 2});
            }
            document.getElementById('line-table').addEventListener('change', function (e) {
                const row = e.target.closest('.line-row');
                if (!row) return;
                if (e.target.classList.contains('item-select')) {
                    const selected = e.target.options[e.target.selectedIndex];
                    const price = selected ? selected.dataset.price : null;
                    if (price) row.querySelector('.price-input').value = price;
                }
                recalcRow(row);
            });
            document.getElementById('add-row').addEventListener('click', function () {
                const template = document.getElementById('line-row-template').content.cloneNode(true);
                document.querySelector('#line-table tbody').appendChild(template);
                if (window.feather) feather.replace();
            });
            document.getElementById('line-table').addEventListener('click', function (e) {
                if (e.target.closest('.remove-row')) {
                    e.target.closest('.line-row').remove();
                }
            });
        </script>
        @endpush
    @endif
@endsection
