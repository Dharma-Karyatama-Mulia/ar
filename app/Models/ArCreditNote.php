<?php

namespace App\Models;

use App\Models\Concerns\HasWorkflowStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArCreditNote extends Model
{
    use HasWorkflowStatus, SoftDeletes;

    protected $table = 'ar_credit_notes';

    protected $fillable = [
        'credit_note_no', 'ar_invoice_id', 'customer_id', 'reason', 'notes',
        'tax_invoice_number', 'status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ArInvoice::class, 'ar_invoice_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ArCreditNoteLine::class, 'ar_credit_note_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getTotalAttribute(): float
    {
        return $this->lines->sum(fn (ArCreditNoteLine $line) => $line->amount);
    }
}
