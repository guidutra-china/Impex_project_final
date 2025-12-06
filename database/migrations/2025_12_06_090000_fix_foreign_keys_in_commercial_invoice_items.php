<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop old foreign key that points to sales_invoices
        Schema::table('commercial_invoice_items', function (Blueprint $table) {
            // Try to drop the old foreign key if it exists
            try {
                $table->dropForeign('sales_invoice_items_sales_invoice_id_foreign');
            } catch (\Exception $e) {
                // Foreign key might not exist or have different name
            }
        });
        
        // Add correct foreign key pointing to commercial_invoices
        Schema::table('commercial_invoice_items', function (Blueprint $table) {
            $table->foreign('commercial_invoice_id')
                ->references('id')
                ->on('commercial_invoices')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commercial_invoice_items', function (Blueprint $table) {
            $table->dropForeign(['commercial_invoice_id']);
        });
    }
};
