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
            
            // Basic Information
            $table->string('name');
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->integer('price')->nullable()->comment('Price in cents');
            $table->enum('status', ['active', 'inactive'])->default('active');
            
            // Relationships
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('supplier_code')->nullable();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('customer_code')->nullable();
            
            // International Trade Information
            $table->string('hs_code')->nullable()->comment('Harmonized System Code');
            $table->string('origin_country')->nullable();
            $table->string('brand')->nullable();
            $table->string('model_number')->nullable();
            
            // Order Information
            $table->integer('moq')->nullable()->comment('Minimum Order Quantity');
            $table->string('moq_unit')->nullable()->comment('Unit for MOQ (pcs, cartons, etc)');
            $table->integer('lead_time_days')->nullable()->comment('Production lead time in days');
            $table->text('certifications')->nullable()->comment('Product certifications (CE, FDA, etc)');
            
            // Packing Information - Product Level
            $table->decimal('net_weight', 10, 3)->nullable()->comment('Net weight in kg');
            $table->decimal('gross_weight', 10, 3)->nullable()->comment('Gross weight in kg');
            $table->decimal('product_length', 10, 2)->nullable()->comment('Length in cm');
            $table->decimal('product_width', 10, 2)->nullable()->comment('Width in cm');
            $table->decimal('product_height', 10, 2)->nullable()->comment('Height in cm');
            
            // Packing Information - Inner Box
            $table->integer('pcs_per_inner_box')->nullable();
            $table->decimal('inner_box_length', 10, 2)->nullable()->comment('cm');
            $table->decimal('inner_box_width', 10, 2)->nullable()->comment('cm');
            $table->decimal('inner_box_height', 10, 2)->nullable()->comment('cm');
            $table->decimal('inner_box_weight', 10, 3)->nullable()->comment('kg');
            
            // Packing Information - Master Carton
            $table->integer('pcs_per_carton')->nullable();
            $table->integer('inner_boxes_per_carton')->nullable();
            $table->decimal('carton_length', 10, 2)->nullable()->comment('cm');
            $table->decimal('carton_width', 10, 2)->nullable()->comment('cm');
            $table->decimal('carton_height', 10, 2)->nullable()->comment('cm');
            $table->decimal('carton_weight', 10, 3)->nullable()->comment('kg');
            $table->decimal('carton_cbm', 10, 4)->nullable()->comment('Cubic meters');
            
            // Container Loading
            $table->integer('cartons_per_20ft')->nullable();
            $table->integer('cartons_per_40ft')->nullable();
            $table->integer('cartons_per_40hq')->nullable();
            
            // Additional Notes
            $table->text('packing_notes')->nullable();
            $table->text('internal_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('sku');
            $table->index('status');
            $table->index('supplier_id');
            $table->index('client_id');
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
