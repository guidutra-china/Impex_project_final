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
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('component_product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('quantity', 10, 2);
            $table->string('unit_of_measure', 255)->default('pcs');
            $table->decimal('waste_factor', 10, 2)->default(0);
            $table->decimal('actual_quantity', 10, 2)->default(0);
            $table->integer('unit_cost')->default(0);
            $table->integer('total_cost')->default(0);
            $table->integer('sort_order')->default(0)->comment('Display order');
            $table->text('notes')->nullable();
            $table->boolean('is_optional')->default(false);
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
