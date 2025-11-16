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
            $table->foreignId('supplier_quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->unsigned();
            
            // Prices in cents (integers)
            $table->integer('unit_price_before_commission')->unsigned();
            $table->integer('unit_price_after_commission')->unsigned();
            $table->integer('total_price_before_commission')->unsigned();
            $table->integer('total_price_after_commission')->unsigned();
            
            // Converted price in order currency (cents)
            $table->integer('converted_price_cents')->unsigned()->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['supplier_quote_id', 'product_id']);
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
