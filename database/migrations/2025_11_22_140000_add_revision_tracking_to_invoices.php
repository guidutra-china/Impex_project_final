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
        // Add revision tracking to purchase_invoices
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->foreignId('superseded_by_id')
                ->nullable()
                ->after('revision_number')
                ->constrained('purchase_invoices')
                ->nullOnDelete();
            
            $table->foreignId('supersedes_id')
                ->nullable()
                ->after('superseded_by_id')
                ->constrained('purchase_invoices')
                ->nullOnDelete();
        });

        // Add revision tracking to sales_invoices
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->foreignId('superseded_by_id')
                ->nullable()
                ->after('revision_number')
                ->constrained('sales_invoices')
                ->nullOnDelete();
            
            $table->foreignId('supersedes_id')
                ->nullable()
                ->after('superseded_by_id')
                ->constrained('sales_invoices')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropForeign(['superseded_by_id']);
            $table->dropForeign(['supersedes_id']);
            $table->dropColumn(['superseded_by_id', 'supersedes_id']);
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropForeign(['superseded_by_id']);
            $table->dropForeign(['supersedes_id']);
            $table->dropColumn(['superseded_by_id', 'supersedes_id']);
        });
    }
};
