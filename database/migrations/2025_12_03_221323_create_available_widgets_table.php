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
        Schema::create('available_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('widget_id')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('class');
            $table->string('icon')->nullable();
            $table->string('category')->default('general');
            $table->boolean('is_available')->default(true);
            $table->boolean('default_visible')->default(false);
            $table->string('requires_permission')->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('is_available');
            $table->index('default_visible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('available_widgets');
    }
};
