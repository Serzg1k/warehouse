<?php

namespace App\Repositories;

use App\Models\InventoryMovement;
use App\Models\Order;
use Illuminate\Support\Collection;

class InventoryMovementRepository
{
    /**
     * Create movement when stock is reserved from local inventory.
     */
    public function createReserveFromLocalStock(Order $order): InventoryMovement
    {
        return InventoryMovement::create([
            'sku' => $order->sku,
            'order_id' => $order->id,
            'qty' => -$order->qty,
            'type' => 'reserve',
            'meta' => [
                'reason' => 'local_stock',
            ],
        ]);
    }

    /**
     * Create movement when supplier request is sent.
     */
    public function createSupplierRequest(Order $order, array $response): InventoryMovement
    {
        return InventoryMovement::create([
            'sku' => $order->sku,
            'order_id' => $order->id,
            'qty' => 0,
            'type' => 'supplier_request',
            'meta' => $response,
        ]);
    }

    /**
     * Create movement when supplier rejects request.
     */
    public function createSupplierRejected(Order $order, array $response): InventoryMovement
    {
        return InventoryMovement::create([
            'sku' => $order->sku,
            'order_id' => $order->id,
            'qty' => 0,
            'type' => 'supplier_rejected',
            'meta' => $response,
        ]);
    }

    /**
     * Create movement when supplier physically delivers items.
     */
    public function createSupplierIn(Order $order): InventoryMovement
    {
        return InventoryMovement::create([
            'sku' => $order->sku,
            'order_id' => $order->id,
            'qty' => $order->qty,
            'type' => 'supplier_in',
            'meta' => [
                'ref' => $order->supplier_ref,
            ],
        ]);
    }

    /**
     * Create movement when reserve after supplier fails unexpectedly.
     */
    public function createReserveFailedAfterSupplier(Order $order): InventoryMovement
    {
        return InventoryMovement::create([
            'sku' => $order->sku,
            'order_id' => $order->id,
            'qty' => 0,
            'type' => 'reserve_failed_after_supplier',
            'meta' => [],
        ]);
    }

    /**
     * Create movement when reserve after supplier succeeds.
     */
    public function createReserveAfterSupplier(Order $order): InventoryMovement
    {
        return InventoryMovement::create([
            'sku' => $order->sku,
            'order_id' => $order->id,
            'qty' => -$order->qty,
            'type' => 'reserve_after_supplier',
            'meta' => [],
        ]);
    }

    /**
     * Get all movements for given SKU ordered by creation time.
     */
    public function getBySku(string $sku): Collection
    {
        return InventoryMovement::where('sku', $sku)
            ->orderBy('created_at')
            ->get();
    }
}
