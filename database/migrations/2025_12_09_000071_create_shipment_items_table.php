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
        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('shipment_id');
            $table->bigInteger('sales_order_item_id')->nullable();
            $table->bigInteger('sales_invoice_item_id')->nullable();
            $table->bigInteger('proforma_invoice_item_id')->nullable();
            $table->bigInteger('product_id');
            $table->integer('quantity_ordered');
            $table->integer('quantity_to_ship');
            $table->integer('quantity_shipped');
            $table->string('product_name', 255);
            $table->string('product_sku', 255)->nullable();
            $table->text('product_description');
            $table->string('hs_code', 20)->nullable();
            $table->string('country_of_origin', 2)->nullable();
            $table->bigInteger('unit_price');
            $table->bigInteger('customs_value');
            $table->decimal('unit_weight', 10, 2)->nullable();
            $table->decimal('total_weight', 10, 2)->nullable();
            $table->decimal('unit_volume', 10, 2)->nullable();
            $table->decimal('total_volume', 10, 2)->nullable();
            // TODO: `packing_status` enum('unpacked','partially_packed','fully_packed') COLLATE utf8mb4_unicode_ci DEFAULT 'unpacked'
            $table->integer('quantity_packed');
            $table->integer('quantity_remaining');
            $table->text('notes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_items');
    }
};
