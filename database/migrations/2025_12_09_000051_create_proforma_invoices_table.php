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
        Schema::create('proforma_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('proforma_number', 255);
            $table->integer('revision_number');
            $table->bigInteger('customer_id');
            $table->bigInteger('currency_id');
            $table->bigInteger('payment_term_id')->nullable();
            $table->integer('incoterm')->nullable();
            $table->string('incoterm_location', 255)->nullable();
            $table->bigInteger('subtotal');
            $table->bigInteger('tax');
            $table->bigInteger('total');
            $table->decimal('exchange_rate', 10, 2);
            $table->date('issue_date');
            $table->date('valid_until')->nullable();
            $table->date('due_date')->nullable();
            // TODO: `status` enum('draft','sent','approved','rejected','expired','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft'
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->bigInteger('approved_by')->nullable();
            $table->text('rejection_reason');
            $table->timestamp('rejected_at')->nullable();
            $table->integer('deposit_required');
            $table->bigInteger('deposit_amount')->nullable();
            $table->decimal('deposit_percent', 10, 2)->nullable();
            $table->integer('deposit_received');
            $table->timestamp('deposit_received_at')->nullable();
            $table->string('deposit_payment_method', 255)->nullable();
            $table->string('deposit_payment_reference', 255)->nullable();
            $table->text('notes');
            $table->text('terms_and_conditions');
            $table->text('customer_notes');
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
        Schema::dropIfExists('proforma_invoices');
    }
};
