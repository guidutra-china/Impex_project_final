<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proforma_invoices', function (Blueprint $table) {
            $table->enum('incoterm', [
                'EXW', 'FCA', 'CPT', 'CIP', 'DAP', 'DPU', 'DDP',
                'FAS', 'FOB', 'CFR', 'CIF'
            ])->nullable()->after('payment_term_id')->comment('International Commercial Terms');
            
            $table->string('incoterm_location')->nullable()->after('incoterm')->comment('Ex: Shanghai Port, New York, etc.');
        });
    }

    public function down(): void
    {
        Schema::table('proforma_invoices', function (Blueprint $table) {
            $table->dropColumn(['incoterm', 'incoterm_location']);
        });
    }
};
