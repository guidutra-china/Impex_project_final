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
        Schema::create('packing_boxes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('shipment_id');
            $table->bigInteger('shipment_container_id')->nullable();
            $table->integer('box_number');
            $table->string('box_label', 255)->nullable();
            // TODO: `box_type` enum('carton','pallet','crate','bag','drum','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'carton'
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->decimal('gross_weight', 10, 2)->nullable();
            $table->decimal('net_weight', 10, 2)->nullable();
            $table->decimal('volume', 10, 2)->nullable();
            $table->integer('total_items');
            $table->integer('total_quantity');
            // TODO: `packing_status` enum('empty','packing','sealed','shipped') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'empty'
            $table->timestamp('sealed_at')->nullable();
            $table->bigInteger('sealed_by')->nullable();
            $table->text('notes');
            $table->string('contents_description', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packing_boxes');
    }
};
