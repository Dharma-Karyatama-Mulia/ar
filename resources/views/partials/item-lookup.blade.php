{{--
    Dropdown item dengan pencarian remote (Tom Select). Master items berisi ~31rb baris hasil
    import Accurate — terlalu besar untuk dirender sebagai <option>. Include sekali per halaman:

    - Semua <select class="item-select"> (termasuk baris baru dari <template>) otomatis di-upgrade.
      Server cukup merender <option> untuk item yang sedang terpilih.
    - data-lookup="sold" membatasi ke item is_sold.
    - Memilih item mengisi .price-input di baris yang sama dengan harga jual default.
    - Input bernama lines[][field] di-index ulang jadi lines[i][field] per .line-row saat submit
      (pola lines[][field] membuat PHP memecah tiap field jadi elemen array sendiri).
--}}
@once
    @push('head')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
        <style>
            .ts-wrapper.form-select { padding: 0; border: 0; }
            .ts-wrapper .ts-control { min-height: 38px; }
            .ts-dropdown { z-index: 1060; }
        </style>
    @endpush
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
        <script>
            (function () {
                const lookupUrl = @json(route('lookup.items'));

                function upgrade(el) {
                    if (el.tomselect || el.closest('template')) return;
                    new TomSelect(el, {
                        valueField: 'id',
                        labelField: 'text',
                        searchField: [],
                        maxOptions: 30,
                        loadThrottle: 300,
                        placeholder: 'Ketik kode / nama barang...',
                        shouldLoad: (q) => q.length >= 2,
                        load: function (query, callback) {
                            const params = new URLSearchParams({q: query});
                            if (el.dataset.lookup === 'sold') params.set('sold', '1');
                            fetch(lookupUrl + '?' + params, {headers: {'Accept': 'application/json'}})
                                .then((r) => r.json()).then(callback).catch(() => callback());
                        },
                        onChange: function (value) {
                            const option = this.options[value];
                            const row = el.closest('.line-row');
                            const priceInput = row ? row.querySelector('.price-input') : null;
                            if (option && option.price != null && priceInput) {
                                priceInput.value = option.price;
                                priceInput.dispatchEvent(new Event('change', {bubbles: true}));
                            }
                        },
                        render: {
                            no_results: () => '<div class="no-results p-2 text-muted">Barang tidak ditemukan</div>',
                            not_loading: () => '<div class="p-2 text-muted">Ketik minimal 2 huruf</div>',
                        },
                    });
                }

                function upgradeAll(root) {
                    root.querySelectorAll('select.item-select').forEach(upgrade);
                }

                document.addEventListener('DOMContentLoaded', function () {
                    upgradeAll(document);
                    new MutationObserver(function (mutations) {
                        mutations.forEach((m) => m.addedNodes.forEach((n) => { if (n.nodeType === 1) upgradeAll(n); }));
                    }).observe(document.body, {childList: true, subtree: true});

                    document.querySelectorAll('form').forEach(function (form) {
                        form.addEventListener('submit', function () {
                            form.querySelectorAll('.line-row').forEach(function (row, i) {
                                row.querySelectorAll('[name^="lines["]').forEach(function (input) {
                                    const field = input.dataset.lineField || (input.name.match(/^lines\[\d*\]\[(\w+)\]$/) || [])[1];
                                    if (!field) return;
                                    input.dataset.lineField = field;
                                    input.name = 'lines[' + i + '][' + field + ']';
                                });
                            });
                        });
                    });
                });
            })();
        </script>
    @endpush
@endonce
