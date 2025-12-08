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
        Schema::create('category_features', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('category_id');
            $table->string('feature_name', 255);
            $table->string('default_value', 255)->nullable();
            $table->string('unit', 255)->nullable();
            $table->integer('sort_order');
            $table->integer('is_required');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_features');
    }
};
