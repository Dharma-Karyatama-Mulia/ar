<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArCreditNoteLine extends Model
{
    protected $table = 'ar_credit_note_lines';

    protected $fillable = ['ar_credit_note_id', 'item_id', 'qty', 'unit_price', 'item_override_description'];

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(ArCreditNote::class, 'ar_credit_note_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
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
