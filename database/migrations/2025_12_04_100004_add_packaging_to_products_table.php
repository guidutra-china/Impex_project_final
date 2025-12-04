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
            // Standard packaging information
            $table->integer('standard_packaging_quantity')->nullable()->after('weight')->comment('Units per standard box');
            $table->decimal('package_weight', 10, 2)->nullable()->after('standard_packaging_quantity')->comment('Weight of one standard box in kg');
            $table->string('package_dimensions')->nullable()->after('package_weight')->comment('L x W x H in cm (e.g., 50x30x20)');
            $table->decimal('package_volume', 12, 4)->nullable()->after('package_dimensions')->comment('Volume of one standard box in m³');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'standard_packaging_quantity',
                'package_weight',
                'package_dimensions',
                'package_volume',
            ]);
        });
    }
};
