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
            $table->bigInteger('product_id');
            $table->bigInteger('component_product_id');
            $table->decimal('quantity', 10, 2);
            $table->string('unit_of_measure', 255);
            $table->decimal('waste_factor', 10, 2);
            $table->decimal('actual_quantity', 10, 2);
            $table->integer('unit_cost');
            $table->integer('total_cost');
            $table->integer('sort_order');
            $table->text('notes');
            $table->integer('is_optional');
            $table->timestamps();
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
