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
        Schema::create('shipment_container_items', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('shipment_container_id');
            $table->bigInteger('proforma_invoice_item_id');
            $table->bigInteger('product_id');
            $table->integer('quantity');
            $table->decimal('unit_weight', 10, 2);
            $table->decimal('total_weight', 10, 2);
            $table->decimal('unit_volume', 10, 2);
            $table->decimal('total_volume', 10, 2);
            $table->string('hs_code', 255)->nullable();
            $table->string('country_of_origin', 255)->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->decimal('customs_value', 10, 2);
            $table->bigInteger('packing_box_id')->nullable();
            // TODO: `status` enum('draft','packed','sealed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft'
            $table->integer('shipment_sequence');
            $table->text('notes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_container_items');
    }
};
