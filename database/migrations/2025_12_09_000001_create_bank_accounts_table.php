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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_name', 255);
            $table->string('account_number', 255);
            $table->string('account_type', 255);
            $table->string('bank_name', 255);
            $table->string('bank_branch', 255)->nullable();
            $table->string('swift_code', 255)->nullable();
            $table->string('iban', 255)->nullable();
            $table->string('routing_number', 255)->nullable();
            $table->bigInteger('currency_id');
            $table->bigInteger('current_balance');
            $table->bigInteger('available_balance');
            $table->bigInteger('daily_limit')->nullable();
            $table->bigInteger('monthly_limit')->nullable();
            $table->integer('is_active');
            $table->integer('is_default');
            $table->text('notes');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
