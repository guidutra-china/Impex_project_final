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
        Schema::create('shipment_container_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_container_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proforma_invoice_item_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->integer('quantity');
            $table->decimal('unit_weight', 8, 2);
            $table->decimal('total_weight', 10, 2);
            $table->decimal('unit_volume', 10, 4);
            $table->decimal('total_volume', 12, 4);
            $table->string('hs_code')->nullable();
            $table->string('country_of_origin')->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('customs_value', 12, 2)->default(0);
            $table->foreignId('packing_box_id')->nullable()->constrained();
            $table->enum('status', ['draft', 'packed', 'sealed'])->default('draft');
            $table->integer('shipment_sequence')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['shipment_container_id', 'proforma_invoice_item_id'], 'sci_container_pii_idx');
            $table->index(['proforma_invoice_item_id', 'shipment_sequence'], 'sci_pii_seq_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_container_items');
    }
};
