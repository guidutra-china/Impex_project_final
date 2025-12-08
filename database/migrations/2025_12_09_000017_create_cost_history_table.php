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
            $table->string('costable_type', 255);
            $table->bigInteger('costable_id');
            $table->bigInteger('changed_by')->nullable();
            $table->string('cost_field', 255);
            $table->integer('old_value')->nullable();
            $table->integer('new_value')->nullable();
            $table->integer('difference')->nullable();
            $table->decimal('percentage_change', 10, 2)->nullable();
            $table->string('change_reason', 255)->nullable();
            $table->text('notes');
            $table->text('metadata')->nullable();
            $table->timestamps();
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
