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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 50);
            $table->integer('revision_number')->default(1);
            $table->bigInteger('order_id')->nullable();
            $table->bigInteger('proforma_invoice_id')->nullable();
            $table->bigInteger('supplier_quote_id')->nullable();
            $table->bigInteger('supplier_id');
            $table->bigInteger('currency_id');
            $table->decimal('exchange_rate', 10, 2)->nullable();
            $table->bigInteger('base_currency_id');
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('shipping_cost')->default(0);
            $table->bigInteger('insurance_cost')->default(0);
            $table->bigInteger('other_costs')->default(0);
            $table->bigInteger('discount')->default(0);
            $table->bigInteger('tax')->default(0);
            $table->bigInteger('total')->default(0);
            $table->bigInteger('total_base_currency')->default(0);
            $table->enum('incoterm', ['EXW', 'FCA', 'CPT', 'CIP', 'DAP', 'DPU', 'DDP', 'FAS', 'FOB', 'CFR', 'CIF'])->nullable();
            $table->string('incoterm_location', 255)->nullable();
            $table->boolean('shipping_included_in_price')->default(false);
            $table->boolean('insurance_included_in_price')->default(false);
            $table->bigInteger('payment_term_id')->nullable();
            $table->text('payment_terms_text')->nullable();
            $table->text('delivery_address')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->enum('status', ['draft', 'sent', 'confirmed', 'received', 'paid', 'cancelled'])->default('draft');
            $table->date('po_date')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms_and_conditions')->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('approved_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
