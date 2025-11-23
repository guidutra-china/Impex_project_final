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
        Schema::table('bom_version_items', function (Blueprint $table) {
            // Drop old foreign key constraint
            $table->dropForeign(['component_id']);
            
            // Change component_id to reference products instead of components
            $table->foreign('component_id')
                ->references('id')
                ->on('products')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bom_version_items', function (Blueprint $table) {
            // Drop new foreign key
            $table->dropForeign(['component_id']);
            
            // Restore old foreign key to components table
            $table->foreign('component_id')
                ->references('id')
                ->on('components')
                ->restrictOnDelete();
        });
    }
};
