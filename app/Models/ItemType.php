<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemType extends Model
{
    use SoftDeletes;

    protected $table = 'item_types';

    protected function casts(): array
    {
        return [
            'is_inventory' => 'boolean',
        ];
    }
}
