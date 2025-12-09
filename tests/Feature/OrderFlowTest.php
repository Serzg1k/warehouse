<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Services\SupplierClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Force sync queue for tests so all jobs/listeners run immediately
        Config::set('queue.default', 'sync');
    }

    #[Test]
    public function it_returns_validation_error_for_invalid_order_payload(): void
    {
        // Send invalid payload (qty is missing)
        $response = $this->postJson('/api/order', [
            'sku' => '',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sku', 'qty']);
    }

    #[Test]
    public function order_with_sufficient_local_stock_is_reserved_and_movement_created(): void
    {
        // Arrange: create inventory item with enough stock
        $item = InventoryItem::query()->create([
            'sku' => 'ABC123',
            'qty_available' => 10,
            'qty_reserved' => 0,
        ]);

        // For this scenario we don't expect supplier to be used,
        // but we don't need to fake it explicitly here.

        // Act: create order via API
        $response = $this->postJson('/api/order', [
            'sku' => 'ABC123',
            'qty' => 3,
        ]);

        // Assert response
        $response
            ->assertCreated()
            ->assertJsonFragment([
                'sku' => 'ABC123',
                'qty' => 3,
            ]);

        $orderId = $response->json('id');
        $this->assertNotNull($orderId);

        // Reload models from DB
        $order = Order::query()->findOrFail($orderId);
        $item->refresh();

        // Order status should be reserved
        $this->assertEquals(OrderStatus::RESERVED, $order->status);

        // Inventory should be updated
        $this->assertEquals(7, $item->qty_available); // 10 - 3
        $this->assertEquals(3, $item->qty_reserved);

        // Inventory movement should be created
        $this->assertDatabaseHas('inventory_movements', [
            'sku' => 'ABC123',
            'order_id' => $orderId,
            'type' => 'reserve',
            'qty' => -3,
        ]);
    }

    #[Test]
    public function order_with_insufficient_stock_uses_supplier_and_eventually_becomes_reserved(): void
    {
        // Arrange: inventory with not enough stock
        InventoryItem::query()->create([
            'sku' => 'XYZ999',
            'qty_available' => 0,
            'qty_reserved' => 0,
        ]);

        // Bind deterministic fake SupplierClient so tests are not random
        $this->app->bind(SupplierClient::class, function () {
            // Anonymous class extending SupplierClient to satisfy type-hint
            return new class extends SupplierClient {
                /** @var string|null */
                public ?string $lastRef = null;

                public function reserve(string $sku, int $qty): array
                {
                    // Always accept and return predictable ref
                    $this->lastRef = 'TEST-REF-123';

                    return [
                        'accepted' => true,
                        'ref' => $this->lastRef,
                    ];
                }

                public function status(string $ref): ?string
                {
                    // Always return ok for this test,
                    // so job will mark order as reserved
                    return 'ok';
                }
            };
        });

        // Act: create order via API
        $response = $this->postJson('/api/order', [
            'sku' => 'XYZ999',
            'qty' => 5,
        ]);

        $response->assertCreated();
        $orderId = $response->json('id');

        // Assert: order exists
        $order = Order::query()->findOrFail($orderId);

        // Because queue is sync and SupplierClient::status always returns 'ok',
        // listener + job chain finishes in the same request:
        // 1) status initially set to awaiting_restock
        // 2) job runs and sets status to reserved
        $order->refresh();
        $this->assertEquals(OrderStatus::RESERVED, $order->status);
        $this->assertEquals('TEST-REF-123', $order->supplier_ref);

        // Check movements: supplier_in + reserve_after_supplier
        $this->assertDatabaseHas('inventory_movements', [
            'sku' => 'XYZ999',
            'order_id' => $orderId,
            'type' => 'supplier_in',
            'qty' => 5,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'sku' => 'XYZ999',
            'order_id' => $orderId,
            'type' => 'reserve_after_supplier',
            'qty' => -5,
        ]);
    }

    #[Test]
    public function inventory_movements_endpoint_returns_data_for_given_sku(): void
    {
        // Arrange: create order, item and movements
        $order = Order::query()->create([
            'sku' => 'ABC123',
            'qty' => 3,
            'status' => OrderStatus::RESERVED,
            'supplier_ref' => null,
            'supplier_checks_count' => 0,
        ]);

        InventoryMovement::query()->create([
            'sku' => 'ABC123',
            'order_id' => $order->id,
            'qty' => -3,
            'type' => 'reserve',
            'meta' => ['reason' => 'test'],
        ]);

        // Act
        $response = $this->getJson('/api/inventory/ABC123/movements');

        // Assert
        $response
            ->assertOk()
            ->assertJsonFragment([
                'sku' => 'ABC123',
                'order_id' => $order->id,
                'qty' => -3,
                'type' => 'reserve',
            ]);
    }
}
