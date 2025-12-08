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
        Schema::create('supplier_performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('supplier_id');
            $table->integer('period_year');
            $table->integer('period_month');
            $table->integer('total_orders');
            $table->integer('on_time_deliveries');
            $table->integer('late_deliveries');
            $table->decimal('average_delay_days', 10, 2);
            $table->integer('total_inspections');
            $table->integer('passed_inspections');
            $table->integer('failed_inspections');
            $table->decimal('quality_score', 10, 2);
            $table->bigInteger('total_purchase_value');
            $table->bigInteger('total_orders_value');
            $table->bigInteger('average_order_value');
            $table->decimal('response_time_hours', 10, 2);
            $table->decimal('communication_score', 10, 2);
            $table->decimal('overall_score', 10, 2);
            // TODO: `rating` enum('excellent','good','average','poor','unacceptable') COLLATE utf8mb4_unicode_ci DEFAULT NULL
            $table->text('notes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_performance_metrics');
    }
};
