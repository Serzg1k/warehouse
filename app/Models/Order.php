<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperOrder
 */
class Order extends Model
{
    protected $fillable = [
        'sku',
        'qty',
        'status',
        'supplier_ref',
        'supplier_checks_count',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
    ];

    // Simple helper to check terminal status
    public function isTerminal(): bool
    {
        $status = $this->status;

        return in_array($status, [
            OrderStatus::RESERVED,
            OrderStatus::FAILED,
        ], true);
    }
}
