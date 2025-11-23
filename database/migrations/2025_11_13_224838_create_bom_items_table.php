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
        Schema::create('bom_items', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('component_product_id')->constrained('products')->restrictOnDelete();
            
            // Quantity
            $table->decimal('quantity', 10, 4)->default(1)->comment('Quantity needed per product unit');
            $table->string('unit_of_measure')->default('pcs')->comment('Unit of measure');
            $table->decimal('waste_factor', 5, 2)->default(0)->comment('Waste/scrap percentage (0-100)');
            $table->decimal('actual_quantity', 10, 4)->default(1)->comment('Quantity including waste (calculated)');
            
            // Costs (cached for performance, stored in cents)
            $table->integer('unit_cost')->default(0)->comment('Component unit cost in cents (cached)');
            $table->integer('total_cost')->default(0)->comment('Total cost for this BOM line in cents (calculated)');
            
            // Metadata
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_optional')->default(false)->comment('Optional component');
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'sort_order']);
            $table->index('component_product_id');
            $table->unique(['product_id', 'component_product_id'], 'unique_product_component');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bom_items');
    }
};
