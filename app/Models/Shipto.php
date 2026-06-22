<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shipto extends Model
{
    use SoftDeletes;

    protected $table = 'shiptos';

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
