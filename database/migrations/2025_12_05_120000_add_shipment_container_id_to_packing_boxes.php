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
        Schema::table('packing_boxes', function (Blueprint $table) {
            $table->foreignId('shipment_container_id')
                ->nullable()
                ->after('shipment_id')
                ->constrained('shipment_containers')
                ->nullOnDelete()
                ->comment('Optional: Box/Pallet can be inside a container or loose');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packing_boxes', function (Blueprint $table) {
            $table->dropForeign(['shipment_container_id']);
            $table->dropColumn('shipment_container_id');
        });
    }
};
