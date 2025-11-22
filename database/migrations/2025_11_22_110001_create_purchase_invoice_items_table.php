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
        Schema::create('purchase_invoice_items', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('purchase_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained()->nullOnDelete();
            
            // Product info (cached for history)
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            
            // Quantities and pricing (stored in cents)
            $table->decimal('quantity', 10, 2);
            $table->bigInteger('unit_cost')->comment('In cents');
            $table->bigInteger('total_cost')->comment('In cents');
            
            // Additional info
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('purchase_invoice_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_items');
    }
};
