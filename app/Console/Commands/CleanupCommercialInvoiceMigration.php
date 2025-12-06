<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanupCommercialInvoiceMigration extends Command
{
    protected $signature = 'migrate:cleanup-commercial-invoice';
    protected $description = 'Cleanup partial commercial invoice migration to allow re-running';

    public function handle()
    {
        $this->info('Starting cleanup of partial commercial invoice migration...');

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Drop foreign keys first
            $this->info('Dropping foreign keys...');
            $this->dropForeignKeyIfExists('sales_invoices', 'sales_invoices_shipment_id_foreign');
            $this->dropForeignKeyIfExists('sales_invoices', 'sales_invoices_proforma_invoice_id_foreign');

            // Drop columns
            $this->info('Dropping columns...');
            $columns = [
                'shipment_id',
                'proforma_invoice_id',
                'customs_discount_percentage',
                'incoterm',
                'incoterm_location',
                'exporter_name',
                'exporter_address',
                'exporter_tax_id',
                'exporter_country',
                'importer_name',
                'importer_address',
                'importer_tax_id',
                'importer_country',
                'bank_name',
                'bank_account',
                'bank_swift',
                'bank_address',
                'display_options',
                'port_of_loading',
                'port_of_discharge',
                'final_destination',
                'bl_number',
                'container_numbers',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('sales_invoices', $column)) {
                    Schema::table('sales_invoices', function ($table) use ($column) {
                        $table->dropColumn($column);
                    });
                    $this->info("  ✓ Dropped column: {$column}");
                } else {
                    $this->comment("  - Column doesn't exist: {$column}");
                }
            }

            // Remove migration record
            $this->info('Removing migration record...');
            DB::table('migrations')
                ->where('migration', '2025_12_06_000000_refactor_sales_invoices_to_commercial_invoices')
                ->delete();

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $this->info('');
            $this->info('✅ Cleanup completed successfully!');
            $this->info('');
            $this->info('Now you can run: php artisan migrate');
            
            return 0;

        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->error('Error during cleanup: ' . $e->getMessage());
            return 1;
        }
    }

    private function dropForeignKeyIfExists($table, $foreignKey)
    {
        try {
            $exists = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND CONSTRAINT_NAME = ?
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ", [$table, $foreignKey]);

            if (!empty($exists)) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$foreignKey}`");
                $this->info("  ✓ Dropped foreign key: {$foreignKey}");
            } else {
                $this->comment("  - Foreign key doesn't exist: {$foreignKey}");
            }
        } catch (\Exception $e) {
            $this->comment("  - Could not drop foreign key {$foreignKey}: " . $e->getMessage());
        }
    }
}
