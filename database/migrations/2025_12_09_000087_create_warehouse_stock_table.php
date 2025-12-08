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
        Schema::create('warehouse_stock', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('warehouse_id');
            $table->bigInteger('warehouse_location_id')->nullable();
            $table->bigInteger('product_id');
            $table->integer('quantity');
            $table->bigInteger('unit_cost');
            $table->bigInteger('total_value');
            $table->date('last_movement_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_stock');
    }
};
