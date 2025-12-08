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
        Schema::create('supplier_issues', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('supplier_id');
            $table->bigInteger('purchase_order_id')->nullable();
            // TODO: `issue_type` enum('late_delivery','quality_problem','wrong_quantity','wrong_product','damaged_goods','pricing_error','communication_issue','other') COLLATE utf8mb4_unicode_ci NOT NULL
            // TODO: `severity` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium'
            // TODO: `status` enum('open','in_progress','resolved','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open'
            $table->text('description');
            $table->text('resolution');
            $table->date('resolution_date')->nullable();
            $table->bigInteger('financial_impact');
            $table->date('reported_date');
            $table->bigInteger('reported_by')->nullable();
            $table->bigInteger('assigned_to')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_issues');
    }
};
