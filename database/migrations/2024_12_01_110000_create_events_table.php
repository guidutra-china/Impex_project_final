<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            
            // User relationship
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Event details
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start');
            $table->dateTime('end')->nullable();
            $table->boolean('all_day')->default(false);
            $table->string('color', 7)->nullable(); // Hex color
            
            // Event classification
            $table->string('event_type')->default('other'); // payment, shipment, document, meeting, deadline, reminder, other
            
            // Polymorphic relation to related entity
            $table->string('related_type')->nullable(); // Model class name
            $table->unsignedBigInteger('related_id')->nullable(); // Model ID
            
            // Event status
            $table->boolean('is_automatic')->default(false); // Created automatically by system
            $table->boolean('is_completed')->default(false);
            $table->boolean('reminder_sent')->default(false);
            
            $table->softDeletes();
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'start']);
            $table->index(['event_type']);
            $table->index(['related_type', 'related_id']);
            $table->index(['is_completed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
