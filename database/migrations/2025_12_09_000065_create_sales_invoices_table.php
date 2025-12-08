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
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 255);
            $table->integer('revision_number');
            $table->bigInteger('superseded_by_id')->nullable();
            $table->bigInteger('supersedes_id')->nullable();
            $table->bigInteger('client_id');
            $table->bigInteger('shipment_id');
            $table->bigInteger('payment_term_id')->nullable();
            $table->bigInteger('quote_id')->nullable();
            $table->bigInteger('currency_id');
            $table->bigInteger('base_currency_id');
            $table->bigInteger('original_invoice_id')->nullable();
            $table->bigInteger('superseded_by_invoice_id')->nullable();
            $table->text('revision_reason');
            $table->date('invoice_date');
            $table->date('shipment_date')->nullable();
            $table->date('due_date');
            $table->date('payment_date')->nullable();
            $table->decimal('exchange_rate', 10, 2);
            $table->bigInteger('subtotal');
            $table->bigInteger('commission');
            $table->bigInteger('tax');
            $table->bigInteger('total');
            $table->bigInteger('total_base_currency');
            // TODO: `status` enum('draft','sent','paid','overdue','cancelled','superseded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft'
            $table->string('approval_status', 255);
            $table->timestamp('approval_deadline')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('approved_by', 255)->nullable();
            $table->text('rejection_reason');
            $table->integer('deposit_required');
            $table->integer('deposit_amount')->nullable();
            $table->integer('deposit_received');
            $table->timestamp('deposit_received_at')->nullable();
            $table->string('deposit_payment_method', 255)->nullable();
            $table->string('deposit_payment_reference', 255)->nullable();
            $table->string('payment_method', 255)->nullable();
            $table->text('payment_reference');
            $table->text('notes');
            $table->text('terms_and_conditions');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_invoices');
    }
};
