<?php

namespace App\Jobs;

use App\Models\Order;
use App\Repositories\InventoryItemRepository;
use App\Repositories\InventoryMovementRepository;
use App\Repositories\OrderRepository;
use App\Services\SupplierClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CheckSupplierStatusJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Order $order,
    ) {
        // Only serializable data here
    }

    /**
     * Handle supplier status polling.
     */
    public function handle(
        SupplierClient $client,
        InventoryItemRepository $inventoryItemRepository,
        InventoryMovementRepository $inventoryMovementRepository,
        OrderRepository $orderRepository,
    ): void {
        // Always work on the fresh instance to avoid stale data
        $order = $this->order->fresh();

        // If order already in terminal state, do nothing
        if (! $order || $order->isTerminal()) {
            return;
        }

        if (! $order->supplier_ref) {
            $orderRepository->markFailed($order);

            return;
        }

        $status = $client->status($order->supplier_ref); // ok | fail | delayed

        if ($status === 'ok') {
            DB::transaction(function () use ($order, $inventoryItemRepository, $inventoryMovementRepository, $orderRepository) {
                $item = $inventoryItemRepository->firstOrCreateForSku($order->sku);

                // For demo we assume supplier delivered exactly order qty
                $item->addStock($order->qty);

                $inventoryMovementRepository->createSupplierIn($order);

                $item->refresh();

                if (! $item->reserve($order->qty)) {
                    // Extremely unlikely in this simplified model; mark as failed
                    $orderRepository->markFailed($order);

                    $inventoryMovementRepository->createReserveFailedAfterSupplier($order);

                    return;
                }

                $inventoryMovementRepository->createReserveAfterSupplier($order);

                $orderRepository->markReserved($order);
            });

            return;
        }

        if ($status === 'fail') {
            $orderRepository->markFailed($order);

            return;
        }

        // delayed
        $canContinue = $orderRepository->incrementSupplierChecksOrFail($order);

        if (! $canContinue) {
            // Already marked as failed inside repository
            return;
        }

        // Re-schedule check with 15 seconds delay
        self::dispatch($order)->delay(now()->addSeconds(15));
    }
}
