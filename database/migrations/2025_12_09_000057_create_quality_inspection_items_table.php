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
        Schema::create('quality_inspection_items', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('quality_inspection_id');
            $table->bigInteger('product_id');
            $table->integer('quantity_inspected');
            $table->integer('quantity_passed');
            $table->integer('quantity_failed');
            // TODO: `result` enum('passed','failed','conditional') COLLATE utf8mb4_unicode_ci DEFAULT NULL
            $table->text('defects_found');
            $table->text('notes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_inspection_items');
    }
};
