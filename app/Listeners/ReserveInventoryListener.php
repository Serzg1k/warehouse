<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Jobs\CheckSupplierStatusJob;
use App\Repositories\InventoryItemRepository;
use App\Repositories\InventoryMovementRepository;
use App\Repositories\OrderRepository;
use App\Services\SupplierClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class ReserveInventoryListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        protected SupplierClient $supplierClient,
        protected InventoryItemRepository $inventoryItemRepository,
        protected InventoryMovementRepository $inventoryMovementRepository,
        protected OrderRepository $orderRepository,
    ) {
        // Intentionally empty
    }

    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        // If order already processed somehow, just skip
        if ($order->isTerminal()) {
            return;
        }

        DB::transaction(function () use ($order) {
            // Get inventory row with lock, or create empty record if not exists
            $item = $this->inventoryItemRepository->getBySkuForUpdate($order->sku)
                ?? $this->inventoryItemRepository->createEmptyForSku($order->sku);

            // Try to reserve locally
            if ($item->qty_available >= $order->qty) {
                $item->reserve($order->qty);

                $this->inventoryMovementRepository->createReserveFromLocalStock($order);

                $this->orderRepository->markReserved($order);

                return;
            }

            // Not enough stock; talk to supplier
            $response = $this->supplierClient->reserve($order->sku, $order->qty);

            if (! ($response['accepted'] ?? false)) {
                $this->orderRepository->markFailed($order);

                $this->inventoryMovementRepository->createSupplierRejected($order, $response);

                return;
            }

            $this->orderRepository->markAwaitingRestock(
                $order,
                $response['ref'] ?? null,
            );

            $this->inventoryMovementRepository->createSupplierRequest($order, $response);

            // First delayed check after 15 seconds
            CheckSupplierStatusJob::dispatch($order)->delay(now()->addSeconds(15));
        });
    }
}
