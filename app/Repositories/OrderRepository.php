<?php

namespace App\Repositories;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository
{
    /**
     * Create new order with pending status.
     */
    public function create(array $data): Order
    {
        // Ensure status is pending by default
        $data['status'] ??= OrderStatus::PENDING;

        return Order::create([
            'sku' => $data['sku'],
            'qty' => $data['qty'],
            'status' => $data['status'],
        ]);
    }

    /**
     * Find order by id or fail.
     */
    public function findByIdOrFail(int $id): Order
    {
        return Order::findOrFail($id);
    }

    /**
     * Get all orders (for simple demo; in real life better use pagination).
     */
    public function all(): Collection
    {
        return Order::all();
    }

    /**
     * Mark order as reserved and persist changes.
     */
    public function markReserved(Order $order): void
    {
        $order->status = OrderStatus::RESERVED;
        $order->save();
    }

    /**
     * Mark order as failed and persist changes.
     */
    public function markFailed(Order $order): void
    {
        $order->status = OrderStatus::FAILED;
        $order->save();
    }

    /**
     * Mark order as awaiting restock with supplier reference.
     */
    public function markAwaitingRestock(Order $order, ?string $supplierRef): void
    {
        $order->status = OrderStatus::AWAITING_RESTOCK;
        $order->supplier_ref = $supplierRef;
        $order->supplier_checks_count = 0;
        $order->save();
    }

    /**
     * Increment supplier checks count, return true if we can still continue.
     */
    public function incrementSupplierChecksOrFail(Order $order, int $maxChecks = 2): bool
    {
        $order->supplier_checks_count++;

        if ($order->supplier_checks_count >= $maxChecks) {
            $order->status = OrderStatus::FAILED;
            $order->save();

            return false;
        }

        $order->save();

        return true;
    }
}
