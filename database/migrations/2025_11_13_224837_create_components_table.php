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
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            
            // Basic Information
            $table->enum('type', ['raw_material', 'purchased_part', 'sub_assembly', 'packaging'])
                  ->default('raw_material')
                  ->index();
            $table->string('code')->unique()->comment('Component SKU/Part Number');
            $table->string('name');
            $table->text('description')->nullable();
            
            // Relationships
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            
            // Costs (stored in cents for precision)
            $table->integer('unit_cost')->default(0)->comment('Material cost per unit in cents');
            $table->integer('labor_cost_per_unit')->default(0)->comment('Labor cost per unit in cents');
            $table->integer('overhead_cost_per_unit')->default(0)->comment('Overhead cost per unit in cents');
            $table->integer('total_cost_per_unit')->default(0)->comment('Total cost per unit in cents (calculated)');
            
            // Stock Management
            $table->string('unit_of_measure')->default('pcs')->comment('pcs, kg, m, L, etc.');
            $table->decimal('stock_quantity', 10, 3)->default(0)->comment('Current stock quantity');
            $table->decimal('reorder_level', 10, 3)->nullable()->comment('Minimum stock level for reorder');
            $table->integer('lead_time_days')->nullable()->comment('Supplier lead time in days');
            
            // Metadata
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('code');
            $table->index('name');
            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('components');
    }
};
