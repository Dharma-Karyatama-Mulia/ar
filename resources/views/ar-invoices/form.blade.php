@extends('layouts.admin')

@section('title', $invoice->exists ? 'Edit Invoice' : 'Buat Invoice Manual')
@section('breadcrumb', $invoice->exists ? 'Edit Invoice' : 'Buat Invoice Manual')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $invoice->exists ? route('ar-invoices.update', $invoice) : route('ar-invoices.store') }}">
                @csrf
                @if($invoice->exists) @method('PUT') @endif

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">- Pilih Customer -</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" @selected($invoice->customer_id === $customer->id)>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Ship-to</label>
                        <select name="shipto_id" class="form-select">
                            <option value="">- Pilih Ship-to -</option>
                            @foreach($shiptos as $shipto)
                                <option value="{{ $shipto->id }}" @selected($invoice->shipto_id === $shipto->id)>{{ $shipto->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Gudang</label>
                        <select name="warehouse_id" class="form-select">
                            <option value="">- Pilih Gudang -</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @selected($invoice->warehouse_id === $warehouse->id)>{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">PO Number</label>
                        <input type="text" name="po_number" class="form-control" value="{{ $invoice->po_number }}">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Invoice</label>
                        <input type="date" name="invoice_date" class="form-control" required
                               value="{{ $invoice->invoice_date?->format('Y-m-d') ?? now()->format('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Termin (hari)</label>
                        <input type="number" name="term_days" class="form-control" min="0" value="{{ $invoice->term_days ?? 0 }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jatuh Tempo</label>
                        <input type="date" name="due_date" class="form-control" value="{{ $invoice->due_date?->format('Y-m-d') }}">
                        <div class="form-text">Kosongkan agar dihitung otomatis dari tanggal invoice + termin.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Catatan</label>
                        <input type="text" name="notes" class="form-control" value="{{ $invoice->notes }}">
                    </div>
                </div>

                <h6>Item</h6>
                <table class="table" id="line-table">
                    <thead>
                        <tr>
                            <th style="width:25%">Item</th>
                            <th style="width:10%">Qty</th>
                            <th style="width:15%">Harga Satuan</th>
                            <th style="width:15%">Subtotal</th>
                            <th style="width:25%">Override Desc.</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoice->lines ?? [] as $line)
                            <tr class="line-row">
                                <td>
                                    <select name="lines[][item_id]" class="form-select item-select" required>
                                        <option value="">- Pilih Item -</option>
                                        @foreach($items as $item)
                                            <option value="{{ $item->id }}" data-price="{{ $item->sales_price }}" @selected($line->item_id === $item->id)>{{ $item->item_no }} - {{ $item->description }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" name="lines[][qty]" class="form-control qty-input" value="{{ $line->qty }}" required></td>
                                <td><input type="number" step="0.01" name="lines[][unit_price]" class="form-control price-input" value="{{ $line->unit_price }}" required></td>
                                <td class="line-subtotal align-middle">{{ number_format($line->qty * $line->unit_price, 2) }}</td>
                                <td><input type="text" name="lines[][item_override_description]" class="form-control" value="{{ $line->item_override_description }}" placeholder="(opsional)"></td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i data-feather="trash-2"></i></button></td>
                            </tr>
                        @empty
                        @endforelse
                    </tbody>
                </table>
                <button type="button" id="add-row" class="btn btn-sm btn-outline-secondary mb-3">
                    <i data-feather="plus"></i> Tambah Baris
                </button>

                <div>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="{{ $invoice->exists ? route('ar-invoices.show', $invoice) : route('ar-invoices.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>

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
            <td><input type="text" name="lines[][item_override_description]" class="form-control" placeholder="(opsional)"></td>
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
@endsection
