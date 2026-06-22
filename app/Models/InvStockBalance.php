<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabel ini dimiliki app `inv` — di sini cuma model tipis untuk menulis
 * pergerakan stok keluar saat AR Invoice disetujui (lihat ArInvoiceController::approve()).
 */
class InvStockBalance extends Model
{
    protected $table = 'inv_stock_balances';

    protected $fillable = ['item_id', 'warehouse_id', 'qty_on_hand', 'unit_cost'];
}
