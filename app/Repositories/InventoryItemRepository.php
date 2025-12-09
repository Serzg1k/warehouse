<?php

namespace App\Repositories;

use App\Models\InventoryItem;

class InventoryItemRepository
{
    /**
     * Get inventory item by SKU with row lock or null.
     */
    public function getBySkuForUpdate(string $sku): ?InventoryItem
    {
        return InventoryItem::where('sku', $sku)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Create empty inventory record for SKU.
     */
    public function createEmptyForSku(string $sku): InventoryItem
    {
        return InventoryItem::create([
            'sku' => $sku,
            'qty_available' => 0,
            'qty_reserved' => 0,
        ]);
    }

    /**
     * Get or create inventory record for SKU (without explicit lock).
     */
    public function firstOrCreateForSku(string $sku): InventoryItem
    {
        // In this case we don't lock row explicitly;
        // DB transaction will still ensure reasonable consistency for this simplified demo.
        return InventoryItem::firstOrCreate(
            ['sku' => $sku],
            [
                'qty_available' => 0,
                'qty_reserved' => 0,
            ]
        );
    }
}
