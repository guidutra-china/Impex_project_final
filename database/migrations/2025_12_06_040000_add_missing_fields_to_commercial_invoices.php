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
        Schema::table('commercial_invoices', function (Blueprint $table) {
            // Add client_id if not exists
            if (!Schema::hasColumn('commercial_invoices', 'client_id')) {
                $table->foreignId('client_id')
                    ->after('id')
                    ->constrained('clients')
                    ->cascadeOnDelete();
            }
            
            // Add shipment_date if not exists
            if (!Schema::hasColumn('commercial_invoices', 'shipment_date')) {
                $table->date('shipment_date')->nullable()->after('invoice_date');
            }
            
            // Add shipping fields if not exist
            if (!Schema::hasColumn('commercial_invoices', 'port_of_loading')) {
                $table->string('port_of_loading')->nullable();
            }
            
            if (!Schema::hasColumn('commercial_invoices', 'port_of_discharge')) {
                $table->string('port_of_discharge')->nullable();
            }
            
            if (!Schema::hasColumn('commercial_invoices', 'final_destination')) {
                $table->string('final_destination')->nullable();
            }
            
            if (!Schema::hasColumn('commercial_invoices', 'bl_number')) {
                $table->string('bl_number')->nullable()->comment('Bill of Lading number');
            }
            
            if (!Schema::hasColumn('commercial_invoices', 'container_numbers')) {
                $table->text('container_numbers')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commercial_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('commercial_invoices', 'client_id')) {
                $table->dropForeign(['client_id']);
                $table->dropColumn('client_id');
            }
            
            $columnsToRemove = [
                'shipment_date',
                'port_of_loading',
                'port_of_discharge',
                'final_destination',
                'bl_number',
                'container_numbers',
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('commercial_invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
