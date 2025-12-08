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
        Schema::create('quality_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->text('description');
            // TODO: `checkpoint_type` enum('visual','measurement','functional','documentation') COLLATE utf8mb4_unicode_ci NOT NULL
            $table->text('criterion');
            // TODO: `applies_to` enum('all','product_category','specific_product') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all'
            $table->bigInteger('product_category_id')->nullable();
            $table->bigInteger('product_id')->nullable();
            $table->integer('is_active');
            $table->integer('is_mandatory');
            $table->integer('sort_order');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_checkpoints');
    }
};
