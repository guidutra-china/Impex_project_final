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
        Schema::create('proforma_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('proforma_invoice_id');
            $table->bigInteger('supplier_quote_id')->nullable();
            $table->bigInteger('quote_item_id')->nullable();
            $table->bigInteger('product_id');
            $table->string('product_name', 255);
            $table->string('product_sku', 255)->nullable();
            $table->integer('quantity');
            $table->integer('quantity_shipped');
            $table->integer('quantity_remaining');
            $table->integer('shipment_count');
            $table->bigInteger('unit_price');
            $table->bigInteger('commission_amount');
            $table->decimal('commission_percent', 10, 2);
            // TODO: `commission_type` enum('embedded','separate') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'embedded'
            $table->bigInteger('total');
            $table->text('notes');
            $table->integer('delivery_days')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proforma_invoice_items');
    }
};
