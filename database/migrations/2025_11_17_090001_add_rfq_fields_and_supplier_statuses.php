<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add RFQ-specific fields and supplier quotation tracking
     */
    public function up(): void
    {
        // Add customer_nr_rfq field to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_nr_rfq')->nullable()->after('order_number')
                ->comment('Customer reference number for this RFQ');
        });

        // Create rfq_supplier_statuses table for tracking quotation sends
        Schema::create('rfq_supplier_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->boolean('sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->string('sent_method')->nullable()->comment('email or pdf');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['order_id', 'supplier_id'], 'rfq_supplier_unique');
            $table->index('sent');
            $table->index(['order_id', 'sent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfq_supplier_statuses');
        
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('customer_nr_rfq');
        });
    }
};
