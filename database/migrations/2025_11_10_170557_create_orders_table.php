<?php

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->foreignId('client_id')->constrained('clients');
            $table->date('order_date');
            $table->integer('payment_terms_days')->default(30);
            $table->index('payment_terms_days');
            $table->bigInteger('total_amount_cents')->default(0);
            $table->foreignId('currency_id')->constrained('currencies');
            $table->decimal('exchange_rate_to_usd', 12, 5)->nullable();
            $table->bigInteger('total_amount_usd_cents')->nullable();
            $table->index('total_amount_usd_cents');
            $table->string('invoice_number')->nullable();
            $table->string('status')->default(OrderStatusEnum::NEW->value);
            $table->string('payment_status')->default(PaymentStatusEnum::UNPAID->value);
            $table->string('shipping_company')->nullable();
            $table->string('shipping_document')->nullable();
            $table->bigInteger('shipping_value_cents')->nullable();
            $table->bigInteger('shipping_value_usd_cents')->nullable();
            $table->date('etd')->nullable();
            $table->date('eta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
