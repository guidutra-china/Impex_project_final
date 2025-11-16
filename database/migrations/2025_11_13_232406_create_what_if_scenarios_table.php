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
        Schema::create('what_if_scenarios', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Scenario Information
            $table->string('name')->comment('Scenario name (e.g., "10% Cost Reduction", "New Supplier")');
            $table->text('description')->nullable()->comment('Scenario description');
            
            // Scenario Data (JSON)
            $table->json('component_cost_adjustments')->nullable()->comment('Component cost changes: {component_id: new_cost}');
            $table->json('quantity_adjustments')->nullable()->comment('Quantity changes: {component_id: new_quantity}');
            $table->integer('labor_cost_adjustment')->nullable()->comment('Direct labor cost override (cents)');
            $table->integer('overhead_cost_adjustment')->nullable()->comment('Direct overhead cost override (cents)');
            $table->decimal('markup_adjustment', 5, 2)->nullable()->comment('Markup percentage override');
            
            // Calculated Results (in cents)
            $table->integer('scenario_bom_cost')->default(0);
            $table->integer('scenario_total_cost')->default(0);
            $table->integer('scenario_selling_price')->default(0);
            
            // Comparison with Current
            $table->integer('cost_difference')->default(0)->comment('Difference from current cost');
            $table->decimal('cost_difference_percentage', 10, 2)->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index('product_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('what_if_scenarios');
    }
};
