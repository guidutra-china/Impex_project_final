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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            
            // === IDENTIFICAÇÃO ===
            $table->string('account_name');
            $table->string('account_number');
            $table->string('account_type')->comment('checking, savings, business, paypal, wise, crypto');
            
            // === BANCO ===
            $table->string('bank_name');
            $table->string('bank_branch')->nullable();
            $table->string('swift_code')->nullable();
            $table->string('iban')->nullable();
            $table->string('routing_number')->nullable();
            
            // === MOEDA ===
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('restrict');
            
            // === SALDO ===
            $table->bigInteger('current_balance')->default(0)->comment('In cents');
            $table->bigInteger('available_balance')->default(0)->comment('In cents');
            
            // === LIMITES ===
            $table->bigInteger('daily_limit')->nullable()->comment('In cents');
            $table->bigInteger('monthly_limit')->nullable()->comment('In cents');
            
            // === STATUS ===
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            
            // === NOTAS ===
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // === INDEXES ===
            $table->index('account_number');
            $table->index('is_active');
            $table->index('currency_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
