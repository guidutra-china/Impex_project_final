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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->string('title', 255);
            $table->text('description');
            $table->timestamp('start');
            $table->timestamp('end')->nullable();
            $table->integer('all_day');
            $table->string('color', 7)->nullable();
            $table->string('event_type', 255);
            $table->string('related_type', 255)->nullable();
            $table->bigInteger('related_id')->nullable();
            $table->integer('is_automatic');
            $table->integer('is_completed');
            $table->integer('reminder_sent');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
