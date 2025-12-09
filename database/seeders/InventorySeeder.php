<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // SKU with enough stock for local reservation
        InventoryItem::query()->updateOrCreate(
            ['sku' => 'ABC123'],
            [
                'qty_available' => 10,
                'qty_reserved' => 0,
            ]
        );

        // SKU with zero stock to force supplier scenario
        InventoryItem::query()->updateOrCreate(
            ['sku' => 'XYZ999'],
            [
                'qty_available' => 0,
                'qty_reserved' => 0,
            ]
        );
    }
}
