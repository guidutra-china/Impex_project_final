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
        Schema::create('payment_term_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_term_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('percentage'); // 1-100
            $table->unsignedSmallInteger('days_from_invoice');
            $table->unsignedTinyInteger('sort_order')->default(1);
            $table->timestamps();

            $table->unique(['payment_term_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_term_stages');
    }
};
