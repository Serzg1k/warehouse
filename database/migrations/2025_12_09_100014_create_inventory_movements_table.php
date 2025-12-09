<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->string('sku');
            $table->foreignId('order_id')->nullable()->constrained('orders');
            $table->integer('qty'); // can be negative
            $table->string('type'); // reserve, unreserve, restock_in, supplier_reserve, supplier_in, ...
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
