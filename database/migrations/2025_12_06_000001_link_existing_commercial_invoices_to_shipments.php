<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration attempts to link existing commercial_invoices to shipments
     * based on shipment_invoices pivot table.
     */
    public function up(): void
    {
        // Strategy 1: Link via shipment_invoices pivot table
        DB::statement("
            UPDATE commercial_invoices ci
            INNER JOIN shipment_invoices si ON ci.id = si.commercial_invoice_id
            SET ci.shipment_id = si.shipment_id
            WHERE ci.shipment_id IS NULL
            LIMIT 1
        ");
        
        // Strategy 2: If still null, try to find shipment by client and date proximity
        DB::statement("
            UPDATE commercial_invoices ci
            LEFT JOIN shipments s ON s.customer_id = ci.client_id
                AND ABS(DATEDIFF(s.actual_departure_date, ci.invoice_date)) <= 30
            SET ci.shipment_id = s.id
            WHERE ci.shipment_id IS NULL
            AND s.id IS NOT NULL
            ORDER BY ABS(DATEDIFF(s.actual_departure_date, ci.invoice_date)) ASC
        ");
        
        // Log remaining records without shipment
        $orphanedCount = DB::table('commercial_invoices')
            ->whereNull('shipment_id')
            ->count();
        
        if ($orphanedCount > 0) {
            echo "\n⚠️  WARNING: {$orphanedCount} commercial invoices could not be linked to shipments.\n";
            echo "   These records will need manual assignment via the admin panel.\n";
            echo "   Run this query to see them:\n";
            echo "   SELECT id, invoice_number, client_id, invoice_date FROM commercial_invoices WHERE shipment_id IS NULL;\n\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Set all shipment_id to NULL
        DB::table('commercial_invoices')
            ->update(['shipment_id' => null]);
    }
};
