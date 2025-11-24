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
        // Drop allocation tables first (foreign key dependencies)
        Schema::dropIfExists('customer_receipt_allocations');
        Schema::dropIfExists('supplier_payment_allocations');
        Schema::dropIfExists('purchase_invoice_items');
        
        // Drop main tables
        Schema::dropIfExists('customer_receipts');
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('purchase_invoices');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is irreversible as we're removing old structure
        // If needed, restore from backup
    }
};
