<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Read-only di app ini — tabel sls_sales_orders dimiliki & ditulis oleh app sls.
 * ar hanya membaca SO yang sudah selesai untuk membuat AR Invoice darinya.
 */
class SalesOrder extends Model
{
    protected $table = 'sls_sales_orders';

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'requested_ship_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function arInvoice(): HasOne
    {
        return $this->hasOne(ArInvoice::class, 'sls_sales_order_id');
    }

    public function shipto(): BelongsTo
    {
        return $this->belongsTo(Shipto::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class, 'sls_sales_order_id');
    }
}
