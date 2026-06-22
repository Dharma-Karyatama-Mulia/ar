<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArInvoiceLine extends Model
{
    protected $table = 'ar_invoice_lines';

    protected $fillable = ['ar_invoice_id', 'item_id', 'qty', 'unit_price', 'item_override_description', 'tax_id'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ArInvoice::class, 'ar_invoice_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function getAmountAttribute(): float
    {
        return $this->qty * $this->unit_price;
    }

    public function getDisplayDescriptionAttribute(): string
    {
        return $this->item_override_description ?: ($this->item?->description ?? '');
    }
}
