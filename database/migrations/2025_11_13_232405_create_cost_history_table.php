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
        Schema::create('cost_history', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic relationship (can track costs for Components or Products)
            $table->morphs('costable'); // Creates costable_id and costable_type
            
            // User who made the change
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Cost Information (in cents)
            $table->string('cost_field')->comment('Which cost field changed (e.g., "unit_cost", "total_manufacturing_cost")');
            $table->integer('old_value')->nullable()->comment('Previous cost value in cents');
            $table->integer('new_value')->nullable()->comment('New cost value in cents');
            $table->integer('difference')->nullable()->comment('Difference in cents (new - old)');
            $table->decimal('percentage_change', 10, 2)->nullable()->comment('Percentage change');
            
            // Context
            $table->string('change_reason')->nullable()->comment('Reason for cost change');
            $table->text('notes')->nullable()->comment('Additional notes');
            $table->json('metadata')->nullable()->comment('Additional context (e.g., affected products)');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['costable_type', 'costable_id', 'created_at']);
            $table->index('cost_field');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_history');
    }
};
