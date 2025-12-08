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
        Schema::create('shipment_containers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('shipment_id');
            $table->string('container_number', 255);
            // TODO: `container_type` enum('20ft','40ft','40hc','pallet','box') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '40ft'
            $table->bigInteger('container_type_id')->nullable();
            $table->decimal('max_weight', 10, 2);
            $table->decimal('max_volume', 10, 2);
            $table->decimal('current_weight', 10, 2);
            $table->decimal('current_volume', 10, 2);
            // TODO: `status` enum('draft','packed','sealed','in_transit','delivered') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft'
            $table->string('seal_number', 255)->nullable();
            $table->timestamp('sealed_at')->nullable();
            $table->bigInteger('sealed_by')->nullable();
            $table->text('notes');
            $table->bigInteger('created_by');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_containers');
    }
};
