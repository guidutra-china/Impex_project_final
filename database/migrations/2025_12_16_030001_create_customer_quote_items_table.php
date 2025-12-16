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
        Schema::create('customer_quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_quote_id')->constrained('customer_quotes')->onDelete('cascade');
            $table->foreignId('supplier_quote_id')->constrained('supplier_quotes')->onDelete('cascade');
            $table->string('display_name'); // Ex: "Option A", "Supplier 1" (for anonymization)
            $table->integer('price_before_commission')->default(0);
            $table->integer('commission_amount')->default(0);
            $table->integer('price_after_commission')->default(0);
            $table->string('delivery_time')->nullable();
            $table->integer('moq')->nullable();
            $table->text('highlights')->nullable(); // Key selling points for this option
            $table->text('notes')->nullable();
            $table->boolean('is_selected_by_customer')->default(false);
            $table->timestamp('selected_at')->nullable();
            $table->integer('display_order')->default(0); // For custom ordering
            $table->timestamps();
            
            // Indexes
            $table->index('customer_quote_id');
            $table->index('supplier_quote_id');
            $table->index('is_selected_by_customer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_quote_items');
    }
};
