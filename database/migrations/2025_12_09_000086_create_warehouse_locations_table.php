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
        Schema::create('warehouse_locations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('warehouse_id');
            $table->string('location_code', 50);
            $table->string('location_name', 255)->nullable();
            // TODO: `location_type` enum('shelf','pallet','bin','floor') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'shelf'
            $table->decimal('capacity', 10, 2)->nullable();
            $table->integer('is_available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_locations');
    }
};
