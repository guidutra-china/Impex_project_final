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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('avatar', 255)->nullable();
            $table->string('sku', 255);
            $table->text('description');
            $table->integer('price')->nullable();
            $table->bigInteger('currency_id')->nullable();
            $table->integer('bom_material_cost');
            $table->integer('direct_labor_cost');
            $table->integer('direct_overhead_cost');
            $table->integer('total_manufacturing_cost');
            $table->decimal('markup_percentage', 10, 2);
            $table->integer('calculated_selling_price');
            // TODO: `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active'
            $table->bigInteger('category_id')->nullable();
            $table->bigInteger('supplier_id')->nullable();
            $table->string('supplier_code', 255)->nullable();
            $table->bigInteger('client_id')->nullable();
            $table->string('customer_code', 255)->nullable();
            $table->string('hs_code', 255)->nullable();
            $table->string('origin_country', 255)->nullable();
            $table->string('brand', 255)->nullable();
            $table->string('model_number', 255)->nullable();
            $table->integer('moq')->nullable();
            $table->string('moq_unit', 255)->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->text('certifications');
            $table->decimal('net_weight', 10, 2)->nullable();
            $table->decimal('gross_weight', 10, 2)->nullable();
            $table->decimal('product_length', 10, 2)->nullable();
            $table->decimal('product_width', 10, 2)->nullable();
            $table->decimal('product_height', 10, 2)->nullable();
            $table->integer('pcs_per_inner_box')->nullable();
            $table->decimal('inner_box_length', 10, 2)->nullable();
            $table->decimal('inner_box_width', 10, 2)->nullable();
            $table->decimal('inner_box_height', 10, 2)->nullable();
            $table->decimal('inner_box_weight', 10, 2)->nullable();
            $table->integer('pcs_per_carton')->nullable();
            $table->integer('inner_boxes_per_carton')->nullable();
            $table->decimal('carton_length', 10, 2)->nullable();
            $table->decimal('carton_width', 10, 2)->nullable();
            $table->decimal('carton_height', 10, 2)->nullable();
            $table->decimal('carton_weight', 10, 2)->nullable();
            $table->decimal('carton_cbm', 10, 2)->nullable();
            $table->integer('cartons_per_20ft')->nullable();
            $table->integer('cartons_per_40ft')->nullable();
            $table->integer('cartons_per_40hq')->nullable();
            $table->text('packing_notes');
            $table->text('internal_notes');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
