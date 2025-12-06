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
        // Step 0: Clean existing data (fresh start)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('sales_invoice_items')->truncate();
        DB::table('sales_invoice_purchase_orders')->truncate();
        DB::table('sales_invoices')->truncate();
        if (Schema::hasTable('shipment_invoices')) {
            DB::table('shipment_invoices')->where('sales_invoice_id', '!=', null)->delete();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Step 1: Add new fields to sales_invoices before renaming
        Schema::table('sales_invoices', function (Blueprint $table) {
            // Shipment relationship (REQUIRED - CI always linked to shipment)
            $table->foreignId('shipment_id')->after('client_id')->constrained()->cascadeOnDelete();
            
            // Proforma Invoice reference (optional)
            $table->foreignId('proforma_invoice_id')->nullable()->after('shipment_id')->constrained()->nullOnDelete();
            
            // Customs and shipping fields
            $table->decimal('customs_discount_percentage', 5, 2)->default(0)->after('exchange_rate')
                ->comment('Percentage discount for customs declaration');
            $table->string('incoterm', 10)->nullable()->after('customs_discount_percentage');
            $table->string('incoterm_location')->nullable()->after('incoterm');
            
            // Exporter details (company issuing the invoice)
            $table->text('exporter_name')->nullable()->after('incoterm_location');
            $table->text('exporter_address')->nullable()->after('exporter_name');
            $table->string('exporter_tax_id')->nullable()->after('exporter_address');
            $table->string('exporter_country', 2)->nullable()->after('exporter_tax_id');
            
            // Importer details (customer receiving goods)
            $table->text('importer_name')->nullable()->after('exporter_country');
            $table->text('importer_address')->nullable()->after('importer_name');
            $table->string('importer_tax_id')->nullable()->after('importer_address');
            $table->string('importer_country', 2)->nullable()->after('importer_tax_id');
            
            // Bank details for payment
            $table->string('bank_name')->nullable()->after('payment_reference');
            $table->string('bank_account')->nullable()->after('bank_name');
            $table->string('bank_swift')->nullable()->after('bank_account');
            $table->text('bank_address')->nullable()->after('bank_swift');
            
            // Display options (JSON) - controls what to show/hide in PDF
            $table->json('display_options')->nullable()->after('terms_and_conditions')
                ->comment('Options for PDF generation: show_payment_terms, show_bank_info, etc.');
            
            // Shipping details
            $table->date('shipment_date')->nullable()->after('invoice_date');
            $table->string('port_of_loading')->nullable()->after('shipment_date');
            $table->string('port_of_discharge')->nullable()->after('port_of_loading');
            $table->string('final_destination')->nullable()->after('port_of_discharge');
            
            // Document references
            $table->string('bl_number')->nullable()->after('final_destination')->comment('Bill of Lading number');
            $table->string('container_numbers')->nullable()->after('bl_number');
        });
        
        // Step 2: Remove fields that don't make sense for Commercial Invoice
        Schema::table('sales_invoices', function (Blueprint $table) {
            // Remove approval fields (approval is on Proforma, not Commercial Invoice)
            $table->dropColumn([
                'approval_status',
                'approval_deadline',
                'approved_at',
                'approved_by',
                'rejection_reason',
            ]);
            
            // Keep deposit fields for now (can be removed later if not needed)
            // $table->dropColumn([
            //     'deposit_required',
            //     'deposit_amount',
            //     'deposit_received',
            //     'deposit_received_at',
            //     'deposit_payment_method',
            //     'deposit_payment_reference',
            // ]);
        });
        
        // Step 3: Rename the table
        Schema::rename('sales_invoices', 'commercial_invoices');
        
        // Step 4: Rename related tables
        Schema::rename('sales_invoice_items', 'commercial_invoice_items');
        Schema::rename('sales_invoice_purchase_orders', 'commercial_invoice_purchase_orders');
        
        // Step 5: Update foreign key columns in related tables
        
        // Update shipment_invoices
        if (Schema::hasColumn('shipment_invoices', 'sales_invoice_id')) {
            Schema::table('shipment_invoices', function (Blueprint $table) {
                $table->renameColumn('sales_invoice_id', 'commercial_invoice_id');
            });
        }
        
        // Update commercial_invoice_items foreign key
        Schema::table('commercial_invoice_items', function (Blueprint $table) {
            $table->renameColumn('sales_invoice_id', 'commercial_invoice_id');
        });
        
        // Update commercial_invoice_purchase_orders foreign key
        Schema::table('commercial_invoice_purchase_orders', function (Blueprint $table) {
            $table->renameColumn('sales_invoice_id', 'commercial_invoice_id');
        });
        
        // Step 6: Update invoice number prefix in existing records
        DB::table('commercial_invoices')
            ->where('invoice_number', 'LIKE', 'SI-%')
            ->update([
                'invoice_number' => DB::raw("REPLACE(invoice_number, 'SI-', 'CI-')")
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse Step 6: Restore invoice number prefix
        DB::table('commercial_invoices')
            ->where('invoice_number', 'LIKE', 'CI-%')
            ->update([
                'invoice_number' => DB::raw("REPLACE(invoice_number, 'CI-', 'SI-')")
            ]);
        
        // Reverse Step 5: Restore foreign key column names
        Schema::table('commercial_invoice_purchase_orders', function (Blueprint $table) {
            $table->renameColumn('commercial_invoice_id', 'sales_invoice_id');
        });
        
        Schema::table('commercial_invoice_items', function (Blueprint $table) {
            $table->renameColumn('commercial_invoice_id', 'sales_invoice_id');
        });
        
        if (Schema::hasColumn('shipment_invoices', 'commercial_invoice_id')) {
            Schema::table('shipment_invoices', function (Blueprint $table) {
                $table->renameColumn('commercial_invoice_id', 'sales_invoice_id');
            });
        }
        
        // Reverse Step 4: Restore table names
        Schema::rename('commercial_invoice_purchase_orders', 'sales_invoice_purchase_orders');
        Schema::rename('commercial_invoice_items', 'sales_invoice_items');
        
        // Reverse Step 3: Restore main table name
        Schema::rename('commercial_invoices', 'sales_invoices');
        
        // Reverse Step 2: Restore removed fields
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->enum('approval_status', ['pending_approval', 'accepted', 'rejected'])->nullable();
            $table->timestamp('approval_deadline')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->text('rejection_reason')->nullable();
        });
        
        // Reverse Step 1: Remove added fields
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropForeign(['shipment_id']);
            $table->dropColumn([
                'shipment_id',
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
                'shipment_date',
                'port_of_loading',
                'port_of_discharge',
                'final_destination',
                'bl_number',
                'container_numbers',
            ]);
        });
    }
};
