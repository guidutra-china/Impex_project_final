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
        Schema::create('customer_quote_product_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_quote_id')->constrained('customer_quotes')->onDelete('cascade');
            $table->foreignId('quote_item_id')->constrained('quote_items')->onDelete('cascade');
            $table->boolean('is_visible_to_customer')->default(true);
            $table->integer('display_order')->default(0);
            $table->text('custom_notes')->nullable();
            $table->timestamps();

            // Ensure unique selection per quote item per customer quote
            $table->unique(['customer_quote_id', 'quote_item_id'], 'cq_product_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_quote_product_selections');
    }
};
