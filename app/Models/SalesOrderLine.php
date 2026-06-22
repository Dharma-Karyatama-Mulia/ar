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

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
