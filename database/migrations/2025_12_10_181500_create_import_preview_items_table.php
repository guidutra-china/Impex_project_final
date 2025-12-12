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
        Schema::create('import_preview_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_history_id')->constrained()->onDelete('cascade');
            
            // Row information
            $table->integer('row_number');
            $table->json('raw_data'); // Original data from Excel/PDF
            
            // Mapped product data
            $table->string('sku')->nullable();
            $table->string('supplier_code')->nullable();
            $table->string('model_number')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('price')->nullable(); // in cents
            $table->integer('cost')->nullable(); // in cents
            $table->integer('msrp')->nullable(); // in cents
            
            // Physical attributes
            $table->decimal('gross_weight', 10, 2)->nullable();
            $table->decimal('net_weight', 10, 2)->nullable();
            $table->decimal('product_length', 10, 2)->nullable();
            $table->decimal('product_width', 10, 2)->nullable();
            $table->decimal('product_height', 10, 2)->nullable();
            
            // Carton/Packaging
            $table->decimal('carton_length', 10, 2)->nullable();
            $table->decimal('carton_width', 10, 2)->nullable();
            $table->decimal('carton_height', 10, 2)->nullable();
            $table->decimal('carton_weight', 10, 2)->nullable();
            $table->decimal('carton_cbm', 10, 4)->nullable();
            $table->integer('pcs_per_carton')->nullable();
            $table->integer('pcs_per_inner_box')->nullable();
            
            // Logistics
            $table->integer('moq')->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->string('hs_code')->nullable();
            
            // Additional fields
            $table->string('brand')->nullable();
            $table->text('certifications')->nullable();
            $table->text('features')->nullable(); // JSON array of features
            // Photo management
            $table->string('photo_path')->nullable(); // Final photo path after import
            $table->string('photo_temp_path')->nullable(); // Temp path of extracted photo
            $table->string('photo_url')->nullable(); // External URL if provided
            $table->enum('photo_status', ['none', 'extracted', 'missing', 'uploaded', 'error'])->default('none');
            $table->boolean('photo_extracted')->default(false);
            $table->text('photo_error')->nullable(); // Error message if extraction failed
            
            // Duplicate detection
            $table->enum('duplicate_status', ['new', 'duplicate', 'similar'])->default('new');
            $table->foreignId('existing_product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->decimal('similarity_score', 5, 2)->nullable(); // 0-100%
            $table->json('differences')->nullable(); // List of differences with existing product
            
            // Import decision
            $table->enum('action', ['import', 'skip', 'update', 'merge'])->default('import');
            $table->boolean('selected')->default(true); // User can unselect items
            $table->text('notes')->nullable(); // User notes
            
            // Validation
            $table->json('validation_errors')->nullable();
            $table->json('validation_warnings')->nullable();
            $table->boolean('has_errors')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index('import_history_id');
            $table->index('duplicate_status');
            $table->index('action');
            $table->index('selected');
            $table->index('existing_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_preview_items');
    }
};
