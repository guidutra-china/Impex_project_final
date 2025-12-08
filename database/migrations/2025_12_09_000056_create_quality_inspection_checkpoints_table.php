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
        Schema::create('quality_inspection_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('quality_inspection_id');
            $table->bigInteger('quality_checkpoint_id');
            // TODO: `result` enum('pass','fail','n/a') COLLATE utf8mb4_unicode_ci NOT NULL
            $table->string('measured_value', 255)->nullable();
            $table->string('expected_value', 255)->nullable();
            $table->text('notes');
            $table->bigInteger('checked_by')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_inspection_checkpoints');
    }
};
