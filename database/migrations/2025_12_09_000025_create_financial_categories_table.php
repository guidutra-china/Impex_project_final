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
        Schema::create('financial_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('code', 20)->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['expense', 'revenue', 'exchange_variation'])->default('expense')->comment('Type of financial category');
            $table->foreignId('parent_id')->nullable()->constrained('financial_categories')->onDelete('cascade');
            $table->boolean('is_active')->default(true)->comment('Indicates if category is active');
            $table->boolean('is_system')->default(false)->comment('System categories cannot be deleted');
            $table->integer('sort_order')->default(0)->comment('Display order');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_categories');
    }
};
