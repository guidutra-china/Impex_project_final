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
        Schema::create('commercial_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('commercial_invoice_id');
            $table->bigInteger('product_id');
            $table->text('description');
            $table->bigInteger('purchase_order_id')->nullable();
            $table->bigInteger('purchase_order_item_id')->nullable();
            $table->bigInteger('quote_item_id')->nullable();
            $table->string('product_name', 255);
            $table->string('product_sku', 255)->nullable();
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 20)->nullable();
            $table->integer('quantity_shipped');
            $table->integer('quantity_remaining');
            // TODO: `shipment_status` enum('not_shipped','partially_shipped','fully_shipped') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_shipped'
            $table->bigInteger('unit_price');
            $table->bigInteger('commission');
            $table->bigInteger('total');
            $table->string('hs_code', 255)->nullable();
            $table->string('country_of_origin', 100)->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('volume', 10, 2)->nullable();
            $table->text('notes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commercial_invoice_items');
    }
};
