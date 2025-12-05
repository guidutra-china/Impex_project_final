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
        // Add category to container_types
        Schema::table('container_types', function (Blueprint $table) {
            $table->enum('category', ['container', 'pallet', 'other'])
                ->default('container')
                ->after('code')
                ->comment('Type category for unified management');
            
            $table->string('unit_system', 20)
                ->default('metric')
                ->after('category')
                ->comment('Measurement system: metric (meters) or imperial');
            
            $table->index('category');
        });

        // Add category to packing_box_types
        Schema::table('packing_box_types', function (Blueprint $table) {
            $table->enum('category', ['carton_box', 'pallet', 'other'])
                ->default('carton_box')
                ->after('code')
                ->comment('Type category for unified management');
            
            $table->string('unit_system', 20)
                ->default('centimeters')
                ->after('category')
                ->comment('Measurement system: centimeters or inches');
            
            $table->index('category');
        });

        // Update existing records
        DB::table('container_types')->update([
            'category' => 'container',
            'unit_system' => 'metric'
        ]);

        DB::table('packing_box_types')->update([
            'category' => 'carton_box',
            'unit_system' => 'centimeters'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('container_types', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn(['category', 'unit_system']);
        });

        Schema::table('packing_box_types', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn(['category', 'unit_system']);
        });
    }
};
