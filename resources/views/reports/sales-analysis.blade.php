@extends('layouts.admin')

@section('title', 'Sales Analysis')
@section('breadcrumb', 'Sales Analysis')

@section('content')
    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3">
                    <label class="form-label">Group By</label>
                    <select name="group_by" class="form-select" onchange="this.form.submit()">
                        @foreach($groupByLabels as $key => $label)
                            <option value="{{ $key }}" {{ $groupBy === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $dateFrom->format('Y-m-d') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $dateTo->format('Y-m-d') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Salesman</label>
                    <select name="salesman_id" class="form-select">
                        <option value="">Semua</option>
                        @foreach($salesmen as $s)
                            <option value="{{ $s->id }}" {{ (string) request('salesman_id') === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-select">
                        <option value="">Semua</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ (string) request('customer_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipe Item</label>
                    <select name="item_type_id" class="form-select">
                        <option value="">Semua</option>
                        @foreach($itemTypes as $it)
                            <option value="{{ $it->id }}" {{ (string) request('item_type_id') === (string) $it->id ? 'selected' : '' }}>{{ $it->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Item</label>
                    <select name="item_id" class="form-select">
                        <option value="">Semua</option>
                        @foreach($items as $i)
                            <option value="{{ $i->id }}" {{ (string) request('item_id') === (string) $i->id ? 'selected' : '' }}>{{ $i->item_no }} - {{ $i->description }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-secondary w-100">Filter</button>
                </div>
            </form>

            @if($groups->isEmpty())
                <p class="text-center text-muted">Tidak ada transaksi di rentang/filter ini.</p>
            @else
                @include('reports.partials.sales-analysis-group', ['groups' => $groups])

                <div class="border-top border-2 pt-2 mt-2 text-end">
                    <strong>Grand Total — Qty: {{ rtrim(rtrim(number_format($grandTotal['qty'], 4), '0'), '.') }} — {{ number_format($grandTotal['amount'], 2) }}</strong>
                </div>
            @endif
        </div>
    </div>
@endsection
