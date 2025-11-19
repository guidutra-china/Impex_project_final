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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            
            // === IDENTIFICAÇÃO ===
            $table->string('shipment_number', 50)->unique()->comment('Ex: SHP-2025-0001');
            
            // === RELACIONAMENTO ===
            $table->foreignId('sales_order_id')->nullable()->constrained('orders')->onDelete('restrict');
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->onDelete('restrict');
            
            // === TIPO ===
            $table->enum('shipment_type', ['outgoing', 'incoming'])->default('outgoing');
            
            // === CARRIER ===
            $table->string('carrier')->nullable()->comment('DHL, FedEx, Maersk, etc');
            $table->string('tracking_number')->nullable()->index();
            $table->string('container_number')->nullable();
            
            // === MÉTODO ===
            $table->enum('shipping_method', ['air', 'sea', 'land', 'courier'])->nullable();
            
            // === STATUS ===
            $table->enum('status', [
                'pending',
                'preparing',
                'ready_to_ship',
                'picked_up',
                'in_transit',
                'customs_clearance',
                'out_for_delivery',
                'delivered',
                'cancelled',
                'returned'
            ])->default('pending')->index();
            
            // === ENDEREÇOS ===
            $table->text('origin_address')->nullable();
            $table->text('destination_address')->nullable();
            
            // === DATAS ===
            $table->date('shipment_date')->nullable();
            $table->date('estimated_delivery_date')->nullable();
            $table->date('actual_delivery_date')->nullable();
            
            // === CUSTOS ===
            $table->bigInteger('shipping_cost')->default(0)->comment('In cents');
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('set null');
            
            // === PESO/VOLUME ===
            $table->decimal('total_weight', 10, 2)->nullable()->comment('In kg');
            $table->decimal('total_volume', 10, 2)->nullable()->comment('In m³');
            
            // === NOTAS ===
            $table->text('notes')->nullable();
            $table->text('special_instructions')->nullable();
            
            // === NOTIFICAÇÕES ===
            $table->timestamp('notification_sent_at')->nullable();
            
            // === AUDITORIA ===
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // === INDEXES ===
            $table->index(['status', 'shipment_date'], 'idx_status_date');
            $table->index(['carrier', 'tracking_number'], 'idx_carrier_tracking');
            $table->index(['sales_order_id', 'status'], 'idx_sales_order_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
