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
            $table->integer('revision_number');
            $table->bigInteger('order_id')->nullable();
            $table->bigInteger('proforma_invoice_id')->nullable();
            $table->bigInteger('supplier_quote_id')->nullable();
            $table->bigInteger('supplier_id');
            $table->bigInteger('currency_id');
            $table->decimal('exchange_rate', 10, 2);
            $table->bigInteger('base_currency_id');
            $table->bigInteger('subtotal');
            $table->bigInteger('shipping_cost');
            $table->bigInteger('insurance_cost');
            $table->bigInteger('other_costs');
            $table->bigInteger('discount');
            $table->bigInteger('tax');
            $table->bigInteger('total');
            $table->bigInteger('total_base_currency');
            // TODO: `incoterm` enum('EXW','FCA','CPT','CIP','DAP','DPU','DDP','FAS','FOB','CFR','CIF') COLLATE utf8mb4_unicode_ci DEFAULT NULL
            $table->string('incoterm_location', 255)->nullable();
            $table->integer('shipping_included_in_price');
            $table->integer('insurance_included_in_price');
            $table->bigInteger('payment_term_id')->nullable();
            $table->text('payment_terms_text');
            $table->text('delivery_address');
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            // TODO: `status` enum('draft','sent','confirmed','received','paid','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft'
            $table->date('po_date');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason');
            $table->text('notes');
            $table->text('terms_and_conditions');
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
