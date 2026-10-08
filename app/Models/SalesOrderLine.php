<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Read-only — lihat catatan di SalesOrder.php.
 */
class SalesOrderLine extends Model
{
    protected $table = 'sls_sales_order_lines';

    /** Harga setelah diskon baris sls (sama dengan HasLinePricing::net_price di app sls). */
    public function getNetPriceAttribute(): float
    {
        return round($this->unit_price * (1 - ($this->discount_percent ?? 0) / 100), 4);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
