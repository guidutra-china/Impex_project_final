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
            
            // Relationships
            $table->foreignId('proforma_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_quote_id')->nullable()->constrained()->nullOnDelete(); // Traceability
            $table->foreignId('quote_item_id')->nullable()->constrained()->nullOnDelete(); // Source of pricing
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            
            // Product info (cached for display)
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            
            // Quantities
            $table->integer('quantity');
            
            // Pricing (in cents) - WITH commission already applied
            $table->bigInteger('unit_price'); // Price WITH commission (from quote_item.unit_price_after_commission)
            $table->bigInteger('commission_amount'); // Commission for this item (from quote_item)
            $table->decimal('commission_percent', 5, 2)->default(0); // Commission % for reference
            $table->enum('commission_type', ['embedded', 'separate'])->default('embedded');
            $table->bigInteger('total'); // Total for this line (quantity × unit_price)
            
            // Additional info
            $table->text('notes')->nullable();
            $table->integer('delivery_days')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('proforma_invoice_id');
            $table->index('product_id');
            $table->index('supplier_quote_id');
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
