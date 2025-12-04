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
        Schema::create('container_types', function (Blueprint $table) {
            $table->id();
            
            // Basic info
            $table->string('name')->unique();
            $table->string('code')->unique()->comment('20ft, 40ft, 40hc, pallet, etc');
            $table->text('description')->nullable();
            
            // Dimensions
            $table->decimal('length', 8, 2)->comment('In meters');
            $table->decimal('width', 8, 2)->comment('In meters');
            $table->decimal('height', 8, 2)->comment('In meters');
            
            // Capacity
            $table->decimal('max_weight', 10, 2)->comment('In kg');
            $table->decimal('max_volume', 12, 4)->comment('In m³');
            $table->decimal('tare_weight', 10, 2)->nullable()->comment('Empty weight in kg');
            
            // Pricing
            $table->bigInteger('base_cost')->nullable()->comment('In cents');
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('set null');
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('container_types');
    }
};
