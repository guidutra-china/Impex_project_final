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
            
            // === IDENTIFICAÇÃO ===
            $table->string('po_number', 50)->unique()->comment('Ex: PO-2025-0001');
            $table->unsignedInteger('revision_number')->default(1);
            
            // === RELACIONAMENTO ===
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null')->comment('RFQ/Order relacionado');
            $table->foreignId('supplier_quote_id')->nullable()->constrained('supplier_quotes')->onDelete('set null');
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('restrict');
            
            // === MOEDA E CONVERSÃO ===
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('restrict');
            $table->decimal('exchange_rate', 12, 6)->comment('Taxa travada no momento da PO');
            $table->foreignId('base_currency_id')->constrained('currencies')->onDelete('restrict')->comment('Moeda base da empresa');
            
            // === VALORES (em centavos na moeda da PO) ===
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('shipping_cost')->default(0);
            $table->bigInteger('insurance_cost')->default(0);
            $table->bigInteger('other_costs')->default(0);
            $table->bigInteger('discount')->default(0);
            $table->bigInteger('tax')->default(0);
            $table->bigInteger('total')->default(0);
            
            // === VALORES EM MOEDA BASE (convertidos) ===
            $table->bigInteger('total_base_currency')->default(0);
            
            // === INCOTERMS ===
            $table->enum('incoterm', [
                'EXW', 'FCA', 'CPT', 'CIP', 'DAP', 'DPU', 'DDP',  // Qualquer transporte
                'FAS', 'FOB', 'CFR', 'CIF'                         // Marítimo/Fluvial
            ])->nullable();
            $table->string('incoterm_location')->nullable()->comment('Ex: Shanghai Port');
            
            // === FRETE DESTACADO (para invoice) ===
            $table->boolean('shipping_included_in_price')->default(false);
            $table->boolean('insurance_included_in_price')->default(false);
            
            // === PAYMENT TERMS ===
            $table->foreignId('payment_term_id')->nullable()->constrained('payment_terms')->onDelete('set null');
            $table->text('payment_terms_text')->nullable();
            
            // === DELIVERY ===
            $table->text('delivery_address')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            
            // === STATUS ===
            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'sent',
                'confirmed',
                'partially_received',
                'received',
                'cancelled',
                'closed'
            ])->default('draft');
            
            // === DATAS ===
            $table->date('po_date');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            
            // === NOTAS ===
            $table->text('notes')->nullable();
            $table->text('terms_and_conditions')->nullable();
            
            // === AUDITORIA ===
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // === INDEXES ===
            $table->index('po_number');
            $table->index('supplier_id');
            $table->index('po_date');
            $table->index('status');
            $table->index(['supplier_id', 'status'], 'idx_supplier_status');
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
