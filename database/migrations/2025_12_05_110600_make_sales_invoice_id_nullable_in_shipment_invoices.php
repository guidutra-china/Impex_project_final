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
        Schema::table('shipment_invoices', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['sales_invoice_id']);
            
            // Drop the unique index that includes sales_invoice_id
            $table->dropUnique('idx_shipment_invoice_unique');
            
            // Drop the regular index
            $table->dropIndex('idx_sales_invoice');
            
            // Make sales_invoice_id nullable
            $table->foreignId('sales_invoice_id')->nullable()->change();
            
            // Re-add foreign key constraint (nullable)
            $table->foreign('sales_invoice_id')
                ->references('id')
                ->on('sales_invoices')
                ->onDelete('cascade');
            
            // Re-add index for sales_invoice_id
            $table->index('sales_invoice_id', 'idx_sales_invoice');
            
            // Add new unique constraint that allows either sales_invoice_id OR proforma_invoice_id
            // Note: MySQL allows multiple NULL values in unique constraints
            $table->unique(['shipment_id', 'sales_invoice_id'], 'idx_shipment_sales_invoice_unique');
            $table->unique(['shipment_id', 'proforma_invoice_id'], 'idx_shipment_proforma_invoice_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_invoices', function (Blueprint $table) {
            // Drop new unique constraints
            $table->dropUnique('idx_shipment_sales_invoice_unique');
            $table->dropUnique('idx_shipment_proforma_invoice_unique');
            
            // Drop foreign key and index
            $table->dropForeign(['sales_invoice_id']);
            $table->dropIndex('idx_sales_invoice');
            
            // Make sales_invoice_id NOT NULL again
            $table->foreignId('sales_invoice_id')->nullable(false)->change();
            
            // Re-add original foreign key
            $table->foreign('sales_invoice_id')
                ->references('id')
                ->on('sales_invoices')
                ->onDelete('cascade');
            
            // Re-add original unique index
            $table->unique(['shipment_id', 'sales_invoice_id'], 'idx_shipment_invoice_unique');
            $table->index('sales_invoice_id', 'idx_sales_invoice');
        });
    }
};
