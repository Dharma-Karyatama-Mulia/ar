<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabel ini dimiliki app `inv` — lihat catatan di InvStockBalance.php.
 */
class InvStockMovement extends Model
{
    protected $table = 'inv_stock_movements';

    protected $fillable = [
        'item_id', 'warehouse_id', 'qty', 'type', 'unit_cost',
        'source_type', 'source_id', 'moved_at',
    ];

    protected function casts(): array
    {
        return [
            'moved_at' => 'datetime',
        ];
    }
}
