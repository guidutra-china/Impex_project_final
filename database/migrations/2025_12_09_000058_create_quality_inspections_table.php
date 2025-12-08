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
        Schema::create('quality_inspections', function (Blueprint $table) {
            $table->id();
            $table->string('inspection_number', 50);
            $table->string('inspectable_type', 100);
            $table->bigInteger('inspectable_id');
            // TODO: `inspection_type` enum('incoming','in_process','final','random','customer_return') COLLATE utf8mb4_unicode_ci NOT NULL
            // TODO: `status` enum('pending','in_progress','completed','failed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending'
            // TODO: `result` enum('passed','failed','conditional') COLLATE utf8mb4_unicode_ci DEFAULT NULL
            $table->date('inspection_date');
            $table->date('completed_date')->nullable();
            $table->bigInteger('inspector_id')->nullable();
            $table->string('inspector_name', 255)->nullable();
            $table->text('notes');
            $table->text('failure_reason');
            $table->text('corrective_action');
            $table->bigInteger('created_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_inspections');
    }
};
