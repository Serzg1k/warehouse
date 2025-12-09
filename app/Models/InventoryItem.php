<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperInventoryItem
 */
class InventoryItem extends Model
{
    protected $fillable = [
        'sku',
        'qty_available',
        'qty_reserved',
    ];

    // Reserve quantity if enough available
    public function reserve(int $qty): bool
    {
        // Simple in-memory check; true atomicity is achieved via DB::transaction + lockForUpdate in service
        if ($this->qty_available < $qty) {
            return false;
        }

        $this->qty_available -= $qty;
        $this->qty_reserved += $qty;

        return $this->save();
    }

    public function addStock(int $qty): void
    {
        // Increase available stock
        $this->qty_available += $qty;
        $this->save();
    }
}
