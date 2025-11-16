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
        Schema::table('products', function (Blueprint $table) {
            // Manufacturing Costs (stored in cents)
            $table->integer('bom_material_cost')->default(0)->after('price')->comment('Total BOM material cost in cents (calculated)');
            $table->integer('direct_labor_cost')->default(0)->after('bom_material_cost')->comment('Direct labor cost in cents');
            $table->integer('direct_overhead_cost')->default(0)->after('direct_labor_cost')->comment('Direct overhead cost in cents');
            $table->integer('total_manufacturing_cost')->default(0)->after('direct_overhead_cost')->comment('Total manufacturing cost in cents (calculated)');
            
            // Pricing
            $table->decimal('markup_percentage', 5, 2)->default(0)->after('total_manufacturing_cost')->comment('Markup percentage (0-100)');
            $table->integer('calculated_selling_price')->default(0)->after('markup_percentage')->comment('Calculated selling price in cents');
            
            // Indexes
            $table->index('bom_material_cost');
            $table->index('total_manufacturing_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'bom_material_cost',
                'direct_labor_cost',
                'direct_overhead_cost',
                'total_manufacturing_cost',
                'markup_percentage',
                'calculated_selling_price',
            ]);
        });
    }
};
