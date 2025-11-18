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
        Schema::table('quote_items', function (Blueprint $table) {
            // Link to order item (which RFQ item this is responding to)
            $table->foreignId('order_item_id')->nullable()->after('supplier_quote_id')->constrained('order_items')->cascadeOnDelete();
            
            // Supplier-specific fields
            $table->integer('delivery_days')->unsigned()->nullable()->after('converted_price_cents');
            $table->string('supplier_part_number')->nullable()->after('delivery_days');
            $table->text('supplier_notes')->nullable()->after('supplier_part_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->dropForeign(['order_item_id']);
            $table->dropColumn(['order_item_id', 'delivery_days', 'supplier_part_number', 'supplier_notes']);
        });
    }
};
