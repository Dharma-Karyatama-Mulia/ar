<?php

namespace App\Models;

use App\Models\Concerns\HasWorkflowStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArInvoice extends Model
{
    use HasWorkflowStatus, SoftDeletes;

    protected $table = 'ar_invoices';

    protected $fillable = [
        'invoice_no', 'customer_id', 'shipto_id', 'warehouse_id', 'po_number',
        'sls_sales_order_id', 'invoice_date', 'due_date', 'term_days', 'notes',
        'status', 'printed', 'tax_invoice_number', 'tax_invoice_date',
        'dpp_amount', 'ppn_amount', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'tax_invoice_date' => 'date',
            'approved_at' => 'datetime',
            'printed' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function shipto(): BelongsTo
    {
        return $this->belongsTo(Shipto::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sls_sales_order_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ArInvoiceLine::class, 'ar_invoice_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ArPaymentAllocation::class, 'ar_invoice_id');
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(ArCreditNote::class, 'ar_invoice_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getSubtotalAttribute(): float
    {
        return $this->lines->sum(fn (ArInvoiceLine $line) => $line->amount);
    }

    public function getTotalAttribute(): float
    {
        return $this->subtotal + $this->ppn_amount;
    }

    private function confirmedAllocations()
    {
        return $this->allocations->filter(
            fn (ArPaymentAllocation $a) => in_array($a->payment?->status, ['disetujui', 'selesai'], true)
        );
    }

    public function getPaidAmountAttribute(): float
    {
        return $this->confirmedAllocations()->sum('amount');
    }

    public function getDiscTakenAmountAttribute(): float
    {
        return $this->confirmedAllocations()->sum('disc_taken_amount');
    }

    public function getWriteOffAmountAttribute(): float
    {
        return $this->confirmedAllocations()->sum('write_off_amount');
    }

    public function getCreditedAmountAttribute(): float
    {
        return $this->creditNotes->whereIn('status', ['disetujui', 'selesai'])->sum('total');
    }

    public function getOwingAttribute(): float
    {
        return round($this->total - $this->paid_amount - $this->disc_taken_amount - $this->write_off_amount - $this->credited_amount, 2);
    }

    public function getAgeAttribute(): int
    {
        $dueDate = ($this->due_date ?? $this->invoice_date)->copy()->startOfDay();

        return $dueDate->isFuture() ? 0 : $dueDate->diffInDays(now());
    }
}
