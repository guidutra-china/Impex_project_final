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
            $table->string('code', 20);
            $table->text('description');
            // TODO: `type` enum('expense','revenue','exchange_variation') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'expense'
            $table->bigInteger('parent_id')->nullable();
            $table->integer('is_active');
            $table->integer('is_system');
            $table->integer('sort_order');
            $table->timestamp('deleted_at')->nullable();
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
