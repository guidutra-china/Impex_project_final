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
            $table->string('widget_id', 255);
            $table->string('title', 255);
            $table->text('description');
            $table->string('class', 255);
            $table->string('icon', 255)->nullable();
            $table->string('category', 255);
            $table->integer('is_available');
            $table->integer('default_visible');
            $table->string('requires_permission', 255)->nullable();
            $table->timestamps();
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
