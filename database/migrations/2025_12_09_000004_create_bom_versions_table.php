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
        Schema::create('bom_versions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('product_id');
            $table->bigInteger('created_by')->nullable();
            $table->integer('version_number');
            $table->string('version_name', 255)->nullable();
            $table->text('change_notes');
            // TODO: `status` enum('draft','active','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft'
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->integer('bom_material_cost_snapshot');
            $table->integer('direct_labor_cost_snapshot');
            $table->integer('direct_overhead_cost_snapshot');
            $table->integer('total_manufacturing_cost_snapshot');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bom_versions');
    }
};
