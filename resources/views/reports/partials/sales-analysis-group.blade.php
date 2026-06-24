@foreach($groups as $group)
    <div class="mb-2 border rounded">
        <div class="d-flex justify-content-between align-items-center p-2 bg-light">
            <strong>{{ $group['label'] }}</strong>
            <span>Qty: {{ rtrim(rtrim(number_format($group['qty'], 4), '0'), '.') }} — {{ number_format($group['amount'], 2) }}</span>
        </div>
        <div class="p-2">
            @if(isset($group['children']))
                @include('reports.partials.sales-analysis-group', ['groups' => $group['children']])
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>No. Invoice</th>
                                <th>Tanggal</th>
                                <th>Customer</th>
                                <th>Item</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($group['rows'] as $r)
                                <tr>
                                    <td>{{ $r['invoice_no'] }}</td>
                                    <td>{{ $r['invoice_date']->format('d/m/Y') }}</td>
                                    <td>{{ $r['customer_name'] }}</td>
                                    <td>{{ $r['item_label'] }}</td>
                                    <td class="text-end">{{ rtrim(rtrim(number_format($r['qty'], 4), '0'), '.') }}</td>
                                    <td class="text-end">{{ number_format($r['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endforeach
