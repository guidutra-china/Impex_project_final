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
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            // === SHIPMENT TRACKING ===
            $table->integer('quantity_shipped')->default(0)->after('quantity')->comment('Total shipped across all shipments');
            $table->integer('quantity_remaining')->default(0)->after('quantity_shipped')->comment('quantity - quantity_shipped');
            $table->enum('shipment_status', ['not_shipped', 'partially_shipped', 'fully_shipped'])->default('not_shipped')->after('quantity_remaining');
            
            // === INDEX ===
            $table->index('shipment_status', 'idx_shipment_status');
        });
        
        // Initialize quantity_remaining for existing records
        DB::statement('UPDATE sales_invoice_items SET quantity_remaining = quantity WHERE quantity_remaining = 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_invoice_items', function (Blueprint $table) {
            $table->dropIndex('idx_shipment_status');
            $table->dropColumn([
                'quantity_shipped',
                'quantity_remaining',
                'shipment_status',
            ]);
        });
    }
};
