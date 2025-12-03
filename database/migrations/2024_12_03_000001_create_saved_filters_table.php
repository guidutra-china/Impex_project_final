<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_filters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('resource_type'); // e.g., 'orders', 'purchase_orders', etc.
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('filters'); // Store filter configuration
            $table->boolean('is_public')->default(false); // Share with other users
            $table->boolean('is_default')->default(false); // Auto-apply on resource load
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'resource_type']);
            $table->index(['resource_type', 'is_public']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_filters');
    }
};
