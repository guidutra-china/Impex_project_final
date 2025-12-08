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
            $table->bigInteger('payment_term_id');
            $table->integer('percentage');
            $table->integer('days');
            $table->date('calculation_base');
            $table->integer('sort_order');
            $table->timestamps();
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
