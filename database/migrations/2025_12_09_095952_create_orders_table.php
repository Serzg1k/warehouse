<?php

use App\Enums\OrderStatus;
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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('sku');
            $table->unsignedInteger('qty');
            $table->string('status')->default(OrderStatus::PENDING->value);
            $table->string('supplier_ref')->nullable();
            $table->unsignedTinyInteger('supplier_checks_count')->default(0);
            $table->timestamps();

            $table->index('sku');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
