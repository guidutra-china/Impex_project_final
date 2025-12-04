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
        Schema::create('dashboard_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('visible_widgets')->nullable()->default(json_encode([]));
            $table->json('widget_order')->nullable()->default(json_encode([]));
            $table->json('widget_settings')->nullable()->default(json_encode([]));
            $table->timestamps();

            $table->unique('user_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_configurations');
    }
};
