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
            // Proforma Invoice reference
            if (!Schema::hasColumn('commercial_invoices', 'proforma_invoice_id')) {
                $table->foreignId('proforma_invoice_id')->nullable()->after('shipment_id')->constrained()->nullOnDelete();
            }
            
            // Currency and payment
            if (!Schema::hasColumn('commercial_invoices', 'currency_id')) {
                $table->foreignId('currency_id')->nullable()->constrained();
            }
            
            if (!Schema::hasColumn('commercial_invoices', 'payment_term_id')) {
                $table->foreignId('payment_term_id')->nullable()->constrained();
            }
            
            // Incoterms
            if (!Schema::hasColumn('commercial_invoices', 'incoterm')) {
                $table->string('incoterm', 10)->nullable();
            }
            
            if (!Schema::hasColumn('commercial_invoices', 'incoterm_location')) {
                $table->string('incoterm_location')->nullable();
            }
            
            // Exporter details
            if (!Schema::hasColumn('commercial_invoices', 'exporter_name')) {
                $table->text('exporter_name')->nullable();
            }
            
            if (!Schema::hasColumn('commercial_invoices', 'exporter_address')) {
                $table->text('exporter_address')->nullable();
            }
            
            if (!Schema::hasColumn('commercial_invoices', 'exporter_tax_id')) {
                $table->string('exporter_tax_id')->nullable();
            }
            
            if (!Schema::hasColumn('commercial_invoices', 'exporter_country')) {
                $table->string('exporter_country', 100)->nullable();
            }
            
            // Importer details
            if (!Schema::hasColumn('commercial_invoices', 'importer_name')) {
                $table->text('importer_name')->nullable();
            }
            
            if (!Schema::hasColumn('commercial_invoices', 'importer_address')) {
                $table->text('importer_address')->nullable();
            }
            
            if (!Schema::hasColumn('commercial_invoices', 'importer_tax_id')) {
                $table->string('importer_tax_id')->nullable();
            }
            
            if (!Schema::hasColumn('commercial_invoices', 'importer_country')) {
                $table->string('importer_country', 100)->nullable();
            }
            
            // Bank details
            if (!Schema::hasColumn('commercial_invoices', 'bank_name')) {
                $table->string('bank_name')->nullable();
            }
            
            if (!Schema::hasColumn('commercial_invoices', 'bank_account')) {
                $table->string('bank_account')->nullable();
            }
            
            if (!Schema::hasColumn('commercial_invoices', 'bank_swift')) {
                $table->string('bank_swift')->nullable();
            }
            
            if (!Schema::hasColumn('commercial_invoices', 'bank_address')) {
                $table->text('bank_address')->nullable();
            }
            
            // Customs discount
            if (!Schema::hasColumn('commercial_invoices', 'customs_discount_percentage')) {
                $table->decimal('customs_discount_percentage', 5, 2)->default(0);
            }
            
            // Display options
            if (!Schema::hasColumn('commercial_invoices', 'display_options')) {
                $table->json('display_options')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commercial_invoices', function (Blueprint $table) {
            $columns = [
                'proforma_invoice_id',
                'currency_id',
                'payment_term_id',
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
                'customs_discount_percentage',
                'display_options',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('commercial_invoices', $column)) {
                    if (in_array($column, ['proforma_invoice_id', 'currency_id', 'payment_term_id'])) {
                        $table->dropForeign(["commercial_invoices_{$column}_foreign"]);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
