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
        Schema::table('shipment_containers', function (Blueprint $table) {
            $table->foreignId('container_type_id')
                ->nullable()
                ->after('container_type')
                ->constrained('container_types')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_containers', function (Blueprint $table) {
            $table->dropForeign(['container_type_id']);
            $table->dropColumn('container_type_id');
        });
    }
};
