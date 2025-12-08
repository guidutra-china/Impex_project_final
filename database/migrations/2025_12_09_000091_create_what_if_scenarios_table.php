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
            $table->bigInteger('product_id');
            $table->bigInteger('created_by')->nullable();
            $table->string('name', 255);
            $table->text('description');
            // TODO: `component_cost_adjustments` json DEFAULT NULL COMMENT 'Component cost changes: {component_id: new_cost}'
            // TODO: `quantity_adjustments` json DEFAULT NULL COMMENT 'Quantity changes: {component_id: new_quantity}'
            $table->integer('labor_cost_adjustment')->nullable();
            $table->integer('overhead_cost_adjustment')->nullable();
            $table->decimal('markup_adjustment', 10, 2)->nullable();
            $table->integer('scenario_bom_cost');
            $table->integer('scenario_total_cost');
            $table->integer('scenario_selling_price');
            $table->integer('cost_difference');
            $table->decimal('cost_difference_percentage', 10, 2);
            $table->timestamps();
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
