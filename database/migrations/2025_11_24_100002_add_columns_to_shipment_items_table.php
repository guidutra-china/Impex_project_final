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
        Schema::table('shipment_items', function (Blueprint $table) {
            // === NEW RELATIONSHIP ===
            $table->foreignId('sales_invoice_item_id')->nullable()->after('sales_order_item_id')->constrained('sales_invoice_items')->onDelete('cascade');
            
            // === QUANTITY TRACKING ===
            // Note: 'quantity' column exists, we'll rename it later
            $table->integer('quantity_ordered')->default(0)->after('product_id')->comment('From sales invoice');
            $table->integer('quantity_shipped')->default(0)->after('quantity')->comment('Actually shipped (confirmed)');
            
            // === PRODUCT INFO (Enhanced) ===
            $table->text('product_description')->nullable()->after('product_sku');
            
            // === CUSTOMS INFORMATION ===
            $table->string('hs_code', 20)->nullable()->after('product_description')->comment('Harmonized System code');
            $table->string('country_of_origin', 2)->nullable()->after('hs_code')->comment('ISO 2-letter code');
            $table->bigInteger('unit_price')->default(0)->after('country_of_origin')->comment('For customs value in cents');
            $table->bigInteger('customs_value')->default(0)->after('unit_price')->comment('quantity * unit_price in cents');
            
            // === PHYSICAL PROPERTIES ===
            $table->decimal('unit_weight', 10, 3)->nullable()->after('customs_value')->comment('Per unit in kg');
            $table->decimal('total_weight', 10, 3)->nullable()->after('unit_weight')->comment('quantity * unit_weight');
            $table->decimal('unit_volume', 10, 6)->nullable()->after('total_weight')->comment('Per unit in m³');
            $table->decimal('total_volume', 10, 6)->nullable()->after('unit_volume')->comment('quantity * unit_volume');
            
            // === PACKING STATUS ===
            $table->enum('packing_status', ['unpacked', 'partially_packed', 'fully_packed'])->default('unpacked')->after('total_volume');
            $table->integer('quantity_packed')->default(0)->after('packing_status')->comment('How many are in boxes');
            $table->integer('quantity_remaining')->default(0)->after('quantity_packed')->comment('Not yet packed');
            
            // === NOTES ===
            $table->text('notes')->nullable()->after('quantity_remaining');
        });
        
        // Rename 'quantity' to 'quantity_to_ship' in separate statement
        Schema::table('shipment_items', function (Blueprint $table) {
            $table->renameColumn('quantity', 'quantity_to_ship');
        });
        
        // Drop old columns
        Schema::table('shipment_items', function (Blueprint $table) {
            $table->dropColumn(['weight', 'volume']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore original columns first
        Schema::table('shipment_items', function (Blueprint $table) {
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('volume', 10, 2)->nullable();
        });
        
        // Rename back
        Schema::table('shipment_items', function (Blueprint $table) {
            $table->renameColumn('quantity_to_ship', 'quantity');
        });
        
        // Drop new columns
        Schema::table('shipment_items', function (Blueprint $table) {
            $table->dropForeign(['sales_invoice_item_id']);
            $table->dropColumn([
                'sales_invoice_item_id',
                'quantity_ordered',
                'quantity_shipped',
                'product_description',
                'hs_code',
                'country_of_origin',
                'unit_price',
                'customs_value',
                'unit_weight',
                'total_weight',
                'unit_volume',
                'total_volume',
                'packing_status',
                'quantity_packed',
                'quantity_remaining',
                'notes',
            ]);
        });
    }
};
