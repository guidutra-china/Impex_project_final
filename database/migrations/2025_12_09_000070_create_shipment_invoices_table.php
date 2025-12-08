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
        Schema::create('shipment_invoices', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('shipment_id');
            $table->bigInteger('commercial_invoice_id')->nullable();
            $table->bigInteger('proforma_invoice_id')->nullable();
            $table->integer('total_items');
            $table->integer('total_quantity');
            $table->bigInteger('total_value');
            $table->decimal('total_weight', 10, 2);
            $table->decimal('total_volume', 10, 2);
            // TODO: `status` enum('pending','partial_shipped','fully_shipped') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending'
            $table->timestamp('shipped_at')->nullable();
            $table->text('notes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_invoices');
    }
};
