<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArPaymentAllocation extends Model
{
    protected $table = 'ar_payment_allocations';

    protected $fillable = [
        'ar_payment_id', 'ar_invoice_id', 'amount',
        'disc_taken_amount', 'write_off_amount', 'write_off_gl_account_id',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(ArPayment::class, 'ar_payment_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ArInvoice::class, 'ar_invoice_id');
    }

    public function writeOffGlAccount(): BelongsTo
    {
        return $this->belongsTo(GlAccount::class, 'write_off_gl_account_id');
    }
}
