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
        Schema::create('packing_box_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('code', 255);
            // TODO: `category` enum('carton_box','pallet','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'carton_box' COMMENT 'Type category for unified management'
            $table->string('unit_system', 20);
            $table->text('description');
            $table->decimal('length', 10, 2);
            $table->decimal('width', 10, 2);
            $table->decimal('height', 10, 2);
            $table->decimal('max_weight', 10, 2);
            $table->decimal('max_volume', 10, 2);
            $table->decimal('tare_weight', 10, 2)->nullable();
            $table->bigInteger('unit_cost')->nullable();
            $table->bigInteger('currency_id')->nullable();
            $table->integer('is_active');
            $table->bigInteger('created_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packing_box_types');
    }
};
