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
        Schema::table('bom_items', function (Blueprint $table) {
            // Drop old foreign key and unique constraint
            $table->dropForeign(['component_id']);
            $table->dropUnique('unique_product_component');
            $table->dropIndex(['component_id']);
            
            // Rename column
            $table->renameColumn('component_id', 'component_product_id');
        });
        
        Schema::table('bom_items', function (Blueprint $table) {
            // Add new foreign key to products table
            $table->foreign('component_product_id')
                ->references('id')
                ->on('products')
                ->restrictOnDelete();
            
            // Add new unique constraint
            $table->unique(['product_id', 'component_product_id'], 'unique_product_component_product');
            
            // Add new index
            $table->index('component_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bom_items', function (Blueprint $table) {
            // Drop new foreign key and unique constraint
            $table->dropForeign(['component_product_id']);
            $table->dropUnique('unique_product_component_product');
            $table->dropIndex(['component_product_id']);
            
            // Rename column back
            $table->renameColumn('component_product_id', 'component_id');
        });
        
        Schema::table('bom_items', function (Blueprint $table) {
            // Restore old foreign key (note: this assumes components table exists in rollback)
            $table->foreign('component_id')
                ->references('id')
                ->on('components')
                ->restrictOnDelete();
            
            // Restore old unique constraint
            $table->unique(['product_id', 'component_id'], 'unique_product_component');
            
            // Restore old index
            $table->index('component_id');
        });
    }
};
