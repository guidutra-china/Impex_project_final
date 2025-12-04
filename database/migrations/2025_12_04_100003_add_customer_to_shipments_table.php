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
        Schema::table('shipments', function (Blueprint $table) {
            // Add customer relationship
            $table->foreignId('customer_id')->nullable()->after('id')->constrained('clients')->onDelete('restrict');
            
            // Remove container_number if it exists (will be managed in shipment_containers)
            if (Schema::hasColumn('shipments', 'container_number')) {
                $table->dropColumn('container_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'customer_id')) {
                $table->dropForeignKeyIfExists(['customer_id']);
                $table->dropColumn('customer_id');
            }
            
            // Add container_number back
            $table->string('container_number')->nullable();
        });
    }
};
