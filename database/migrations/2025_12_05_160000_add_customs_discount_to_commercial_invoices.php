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
            // === CUSTOMS DISCOUNT ===
            $table->decimal('customs_discount_percentage', 5, 2)
                ->default(0)
                ->after('total_value')
                ->comment('Discount % for customs version (0-100)');
            
            // === DISPLAY OPTIONS (what to show/hide in PDF) ===
            $table->json('display_options')
                ->nullable()
                ->after('customs_discount_percentage')
                ->comment('JSON with display flags for PDF generation');
            
            // === BANK INFORMATION (optional) ===
            $table->string('bank_name')->nullable()->after('payment_terms');
            $table->string('bank_account_number')->nullable()->after('bank_name');
            $table->string('bank_swift_code')->nullable()->after('bank_account_number');
            $table->text('bank_address')->nullable()->after('bank_swift_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commercial_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'customs_discount_percentage',
                'display_options',
                'bank_name',
                'bank_account_number',
                'bank_swift_code',
                'bank_address',
            ]);
        });
    }
};
