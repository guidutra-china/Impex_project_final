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
        Schema::create('packing_lists', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('client_id');
            $table->bigInteger('shipment_id');
            $table->string('packing_list_number', 255);
            $table->date('packing_date')->nullable();
            $table->string('exporter_name', 255)->nullable();
            $table->text('exporter_address');
            $table->string('exporter_tax_id', 100)->nullable();
            $table->string('exporter_country', 100)->nullable();
            $table->string('importer_name', 255)->nullable();
            $table->text('importer_address');
            $table->string('importer_tax_id', 100)->nullable();
            $table->string('importer_country', 100)->nullable();
            $table->string('port_of_loading', 255)->nullable();
            $table->string('port_of_discharge', 255)->nullable();
            $table->string('final_destination', 255)->nullable();
            $table->string('bl_number', 255)->nullable();
            $table->text('container_numbers');
            $table->text('notes');
            // TODO: `display_options` json DEFAULT NULL
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packing_lists');
    }
};
