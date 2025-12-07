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
            
            // Foreign keys
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            
            // Packing List number (auto-generated: PL-YY-NNNN)
            $table->string('packing_list_number')->unique();
            
            // Dates
            $table->date('packing_date')->nullable();
            
            // Exporter details (from Company Settings or manual override)
            $table->string('exporter_name')->nullable();
            $table->text('exporter_address')->nullable();
            $table->string('exporter_tax_id', 100)->nullable();
            $table->string('exporter_country', 100)->nullable();
            
            // Importer details (from Customer or manual override)
            $table->string('importer_name')->nullable();
            $table->text('importer_address')->nullable();
            $table->string('importer_tax_id', 100)->nullable();
            $table->string('importer_country', 100)->nullable();
            
            // Shipping details (from Shipment)
            $table->string('port_of_loading')->nullable();
            $table->string('port_of_discharge')->nullable();
            $table->string('final_destination')->nullable();
            $table->string('bl_number')->nullable();
            $table->text('container_numbers')->nullable();
            
            // Additional info
            $table->text('notes')->nullable();
            
            // Display options (JSON)
            $table->json('display_options')->nullable();
            
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
