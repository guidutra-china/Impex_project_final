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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('customer_id');
            $table->bigInteger('currency_id')->nullable();
            $table->bigInteger('category_id')->nullable();
            $table->string('order_number', 255);
            $table->string('customer_nr_rfq', 255)->nullable();
            $table->enum('status', ['draft', 'pending', 'sent', 'pending_quotes', 'quotes_received', 'under_analysis', 'approved', 'cancelled', 'completed'])->default('draft');
            $table->timestamp('rfq_generated_at')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('quoted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->decimal('commission_percent', 10, 2)->default(0);
            $table->enum('commission_type', ['embedded', 'separate'])->default('embedded');
            $table->string('incoterm', 10)->nullable()->comment('Incoterm code: EXW, FOB, CIF, etc.');
            $table->string('incoterm_location', 255)->nullable();
            $table->decimal('commission_percent_average', 10, 2)->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('notes')->nullable();
            $table->integer('total_amount')->default(0);
            $table->bigInteger('selected_quote_id')->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
