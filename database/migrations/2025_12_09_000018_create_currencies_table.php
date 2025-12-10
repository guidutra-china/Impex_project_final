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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 255);
            $table->string('name', 255);
            $table->string('name_plural', 255);
            $table->string('symbol', 255);
            $table->decimal('exchange_rate', 10, 6)->default(1.000000)->comment('Exchange rate relative to base currency');
            $table->boolean('is_base')->default(false)->comment('Indicates if this is the base currency');
            $table->boolean('is_active')->default(true)->comment('Indicates if currency is active for use');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
