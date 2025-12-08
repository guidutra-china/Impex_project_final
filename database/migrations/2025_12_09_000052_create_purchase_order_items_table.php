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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('purchase_order_id');
            $table->bigInteger('product_id');
            $table->integer('quantity');
            $table->integer('received_quantity');
            $table->integer('allocated_quantity');
            $table->bigInteger('unit_cost');
            $table->bigInteger('total_cost');
            $table->bigInteger('selling_price')->nullable();
            $table->bigInteger('selling_total')->nullable();
            $table->string('product_name', 255)->nullable();
            $table->string('product_sku', 255)->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->text('notes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
