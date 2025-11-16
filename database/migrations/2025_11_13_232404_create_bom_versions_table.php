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
            
            // Relationships
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Version Information
            $table->integer('version_number')->default(1)->comment('Sequential version number');
            $table->string('version_name')->nullable()->comment('Optional version name (e.g., "v1.0", "Production", "Prototype")');
            $table->text('change_notes')->nullable()->comment('Description of changes in this version');
            
            // Status
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft')->index();
            $table->timestamp('activated_at')->nullable()->comment('When this version became active');
            $table->timestamp('archived_at')->nullable()->comment('When this version was archived');
            
            // Snapshot of costs at version creation (in cents)
            $table->integer('bom_material_cost_snapshot')->default(0)->comment('BOM cost at version creation');
            $table->integer('direct_labor_cost_snapshot')->default(0);
            $table->integer('direct_overhead_cost_snapshot')->default(0);
            $table->integer('total_manufacturing_cost_snapshot')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'version_number']);
            $table->index(['product_id', 'status']);
            $table->unique(['product_id', 'version_number'], 'unique_product_version');
        });

        // Pivot table for BOM version items
        Schema::create('bom_version_items', function (Blueprint $table) {
            $table->id();
            
            // Relationships
            $table->foreignId('bom_version_id')->constrained('bom_versions')->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('components')->restrictOnDelete();
            
            // Quantity (snapshot at version creation)
            $table->decimal('quantity', 10, 4)->default(1);
            $table->string('unit_of_measure')->default('pcs');
            $table->decimal('waste_factor', 5, 2)->default(0);
            $table->decimal('actual_quantity', 10, 4)->default(1);
            
            // Cost snapshot (in cents)
            $table->integer('unit_cost_snapshot')->default(0)->comment('Component cost at version creation');
            $table->integer('total_cost_snapshot')->default(0);
            
            // Metadata
            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_optional')->default(false);
            $table->timestamps();
            
            // Indexes
            $table->index(['bom_version_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bom_version_items');
        Schema::dropIfExists('bom_versions');
    }
};
