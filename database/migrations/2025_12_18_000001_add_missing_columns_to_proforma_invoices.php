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
        Schema::table('proforma_invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->after('id');
            $table->unsignedBigInteger('customer_quote_id')->nullable()->after('order_id');
            $table->string('public_token', 64)->nullable()->unique()->after('customer_quote_id');
            $table->enum('status', ['draft', 'sent', 'approved', 'rejected', 'expired', 'cancelled'])
                ->default('draft')
                ->after('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proforma_invoices', function (Blueprint $table) {
            $table->dropColumn(['order_id', 'customer_quote_id', 'public_token', 'status']);
        });
    }
};
