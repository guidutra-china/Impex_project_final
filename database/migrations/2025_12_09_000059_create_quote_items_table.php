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
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('supplier_quote_id');
            $table->bigInteger('order_item_id')->nullable();
            $table->bigInteger('product_id');
            $table->integer('quantity');
            $table->integer('unit_price_before_commission');
            $table->integer('unit_price_after_commission');
            $table->integer('total_price_before_commission');
            $table->integer('total_price_after_commission');
            $table->integer('converted_price_cents')->nullable();
            $table->integer('delivery_days')->nullable();
            $table->string('supplier_part_number', 255)->nullable();
            $table->text('supplier_notes');
            $table->decimal('commission_percent', 10, 2);
            // TODO: `commission_type` enum('embedded','separate') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'embedded'
            $table->text('notes');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_items');
    }
};
