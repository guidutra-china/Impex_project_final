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
        Schema::table('products', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
            $table->text('certifications')->nullable()->change();
            $table->text('packing_notes')->nullable()->change();
            $table->text('internal_notes')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('description')->nullable(false)->change();
            $table->text('certifications')->nullable(false)->change();
            $table->text('packing_notes')->nullable(false)->change();
            $table->text('internal_notes')->nullable(false)->change();
        });
    }
};
