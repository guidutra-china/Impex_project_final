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
        // Step 1: Get ALL foreign keys on the table
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'shipment_invoices' 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");
        
        // Step 2: Drop ALL foreign keys first
        foreach ($foreignKeys as $fk) {
            DB::statement("ALTER TABLE shipment_invoices DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }
        
        // Step 3: Now we can safely drop indexes
        try {
            DB::statement("ALTER TABLE shipment_invoices DROP INDEX `idx_shipment_invoice_unique`");
        } catch (\Exception $e) {
            // Index might not exist
        }
        
        try {
            DB::statement("ALTER TABLE shipment_invoices DROP INDEX `idx_sales_invoice`");
        } catch (\Exception $e) {
            // Index might not exist
        }
        
        // Step 4: Modify column to be nullable
        DB::statement("ALTER TABLE shipment_invoices MODIFY COLUMN sales_invoice_id BIGINT UNSIGNED NULL");
        
        // Step 5: Re-add foreign keys
        DB::statement("
            ALTER TABLE shipment_invoices 
            ADD CONSTRAINT `shipment_invoices_shipment_id_foreign` 
            FOREIGN KEY (`shipment_id`) 
            REFERENCES `shipments`(`id`) 
            ON DELETE CASCADE
        ");
        
        DB::statement("
            ALTER TABLE shipment_invoices 
            ADD CONSTRAINT `shipment_invoices_sales_invoice_id_foreign` 
            FOREIGN KEY (`sales_invoice_id`) 
            REFERENCES `sales_invoices`(`id`) 
            ON DELETE CASCADE
        ");
        
        DB::statement("
            ALTER TABLE shipment_invoices 
            ADD CONSTRAINT `shipment_invoices_proforma_invoice_id_foreign` 
            FOREIGN KEY (`proforma_invoice_id`) 
            REFERENCES `proforma_invoices`(`id`) 
            ON DELETE CASCADE
        ");
        
        // Step 6: Add indexes
        DB::statement("ALTER TABLE shipment_invoices ADD INDEX `idx_sales_invoice` (`sales_invoice_id`)");
        DB::statement("ALTER TABLE shipment_invoices ADD INDEX `idx_proforma_invoice` (`proforma_invoice_id`)");
        
        // Step 7: Add new unique constraints
        DB::statement("
            ALTER TABLE shipment_invoices 
            ADD UNIQUE KEY `idx_shipment_sales_invoice_unique` (`shipment_id`, `sales_invoice_id`)
        ");
        
        DB::statement("
            ALTER TABLE shipment_invoices 
            ADD UNIQUE KEY `idx_shipment_proforma_invoice_unique` (`shipment_id`, `proforma_invoice_id`)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop new unique constraints
        DB::statement("ALTER TABLE shipment_invoices DROP INDEX `idx_shipment_sales_invoice_unique`");
        DB::statement("ALTER TABLE shipment_invoices DROP INDEX `idx_shipment_proforma_invoice_unique`");
        
        // Drop all foreign keys
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'shipment_invoices' 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");
        
        foreach ($foreignKeys as $fk) {
            DB::statement("ALTER TABLE shipment_invoices DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
        }
        
        // Drop indexes
        try {
            DB::statement("ALTER TABLE shipment_invoices DROP INDEX `idx_sales_invoice`");
        } catch (\Exception $e) {}
        
        try {
            DB::statement("ALTER TABLE shipment_invoices DROP INDEX `idx_proforma_invoice`");
        } catch (\Exception $e) {}
        
        // Make sales_invoice_id NOT NULL again
        DB::statement("ALTER TABLE shipment_invoices MODIFY COLUMN sales_invoice_id BIGINT UNSIGNED NOT NULL");
        
        // Re-add original foreign keys
        DB::statement("
            ALTER TABLE shipment_invoices 
            ADD CONSTRAINT `shipment_invoices_shipment_id_foreign` 
            FOREIGN KEY (`shipment_id`) 
            REFERENCES `shipments`(`id`) 
            ON DELETE CASCADE
        ");
        
        DB::statement("
            ALTER TABLE shipment_invoices 
            ADD CONSTRAINT `shipment_invoices_sales_invoice_id_foreign` 
            FOREIGN KEY (`sales_invoice_id`) 
            REFERENCES `sales_invoices`(`id`) 
            ON DELETE CASCADE
        ");
        
        // Re-add original unique index
        DB::statement("
            ALTER TABLE shipment_invoices 
            ADD UNIQUE KEY `idx_shipment_invoice_unique` (`shipment_id`, `sales_invoice_id`)
        ");
        
        DB::statement("ALTER TABLE shipment_invoices ADD INDEX `idx_sales_invoice` (`sales_invoice_id`)");
    }
};
