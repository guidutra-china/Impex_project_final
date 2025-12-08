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
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('base_currency_id');
            $table->bigInteger('target_currency_id');
            $table->decimal('rate', 10, 2);
            $table->decimal('inverse_rate', 10, 2)->nullable();
            $table->date('date');
            $table->string('source', 50)->default('manual');
            $table->string('source_name', 255)->nullable();
            $table->string('status', 50)->default('approved');
            $table->bigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
