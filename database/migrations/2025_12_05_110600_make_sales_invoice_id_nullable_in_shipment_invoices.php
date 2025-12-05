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
        // Get the actual foreign key name
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'shipment_invoices' 
            AND COLUMN_NAME = 'sales_invoice_id'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        $fkName = $foreignKeys[0]->CONSTRAINT_NAME ?? null;
        
        if ($fkName) {
            // Drop the foreign key first
            DB::statement("ALTER TABLE shipment_invoices DROP FOREIGN KEY `{$fkName}`");
        }
        
        // Drop the unique index
        DB::statement("ALTER TABLE shipment_invoices DROP INDEX `idx_shipment_invoice_unique`");
        
        // Drop the regular index if it exists
        try {
            DB::statement("ALTER TABLE shipment_invoices DROP INDEX `idx_sales_invoice`");
        } catch (\Exception $e) {
            // Index might not exist or already dropped
        }
        
        // Modify column to be nullable
        DB::statement("ALTER TABLE shipment_invoices MODIFY COLUMN sales_invoice_id BIGINT UNSIGNED NULL");
        
        // Re-add foreign key constraint
        DB::statement("
            ALTER TABLE shipment_invoices 
            ADD CONSTRAINT `shipment_invoices_sales_invoice_id_foreign` 
            FOREIGN KEY (`sales_invoice_id`) 
            REFERENCES `sales_invoices`(`id`) 
            ON DELETE CASCADE
        ");
        
        // Add index for sales_invoice_id
        DB::statement("ALTER TABLE shipment_invoices ADD INDEX `idx_sales_invoice` (`sales_invoice_id`)");
        
        // Add new unique constraints
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
        
        // Drop foreign key
        DB::statement("ALTER TABLE shipment_invoices DROP FOREIGN KEY `shipment_invoices_sales_invoice_id_foreign`");
        
        // Drop index
        DB::statement("ALTER TABLE shipment_invoices DROP INDEX `idx_sales_invoice`");
        
        // Make sales_invoice_id NOT NULL again
        DB::statement("ALTER TABLE shipment_invoices MODIFY COLUMN sales_invoice_id BIGINT UNSIGNED NOT NULL");
        
        // Re-add original foreign key
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
