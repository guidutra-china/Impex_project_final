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
            $table->integer('bom_material_cost')->nullable()->default(0)->change();
            $table->integer('direct_labor_cost')->nullable()->default(0)->change();
            $table->integer('direct_overhead_cost')->nullable()->default(0)->change();
            $table->integer('total_manufacturing_cost')->nullable()->default(0)->change();
            $table->decimal('markup_percentage', 10, 2)->nullable()->default(0)->change();
            $table->integer('calculated_selling_price')->nullable()->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('bom_material_cost')->nullable(false)->change();
            $table->integer('direct_labor_cost')->nullable(false)->change();
            $table->integer('direct_overhead_cost')->nullable(false)->change();
            $table->integer('total_manufacturing_cost')->nullable(false)->change();
            $table->decimal('markup_percentage', 10, 2)->nullable(false)->change();
            $table->integer('calculated_selling_price')->nullable(false)->change();
        });
    }
};
