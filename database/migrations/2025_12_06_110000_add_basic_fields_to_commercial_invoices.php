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
            // Dates
            if (!Schema::hasColumn('commercial_invoices', 'due_date')) {
                $table->date('due_date')->nullable()->after('invoice_date');
            }
            
            if (!Schema::hasColumn('commercial_invoices', 'payment_date')) {
                $table->date('payment_date')->nullable()->after('due_date');
            }
            
            // Payment info
            if (!Schema::hasColumn('commercial_invoices', 'payment_method')) {
                $table->string('payment_method')->nullable();
            }
            
            if (!Schema::hasColumn('commercial_invoices', 'payment_reference')) {
                $table->text('payment_reference')->nullable();
            }
            
            // Notes
            if (!Schema::hasColumn('commercial_invoices', 'notes')) {
                $table->text('notes')->nullable();
            }
            
            if (!Schema::hasColumn('commercial_invoices', 'terms_and_conditions')) {
                $table->text('terms_and_conditions')->nullable();
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
                'due_date',
                'payment_date',
                'payment_method',
                'payment_reference',
                'notes',
                'terms_and_conditions',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('commercial_invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
