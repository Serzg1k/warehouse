<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperInventoryMovement
 */
class InventoryMovement extends Model
{
    protected $fillable = [
        'sku',
        'order_id',
        'qty',
        'type',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
