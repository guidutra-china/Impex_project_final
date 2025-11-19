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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            
            // === IDENTIFICAÇÃO ===
            $table->string('name');
            $table->enum('type', [
                'bank_transfer',
                'wire_transfer',
                'paypal',
                'credit_card',
                'debit_card',
                'check',
                'cash',
                'wise',
                'cryptocurrency',
                'other'
            ]);
            
            // === CONTA BANCÁRIA ASSOCIADA ===
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->onDelete('set null');
            
            // === TAXAS ===
            $table->enum('fee_type', ['none', 'fixed', 'percentage', 'fixed_plus_percentage'])->default('none');
            $table->bigInteger('fixed_fee')->default(0)->comment('In cents');
            $table->decimal('percentage_fee', 5, 2)->default(0)->comment('Ex: 2.9 for 2.9%');
            
            // === PROCESSAMENTO ===
            $table->enum('processing_time', ['immediate', 'same_day', '1_3_days', '3_5_days', '5_7_days'])->default('immediate');
            
            // === STATUS ===
            $table->boolean('is_active')->default(true);
            
            // === NOTAS ===
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // === INDEXES ===
            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
