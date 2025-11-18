<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Convert supplier_tag to polymorphic taggables table
     */
    public function up(): void
    {
        // Create polymorphic taggables table
        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->morphs('taggable'); // Creates taggable_id and taggable_type
            $table->timestamps();
            
            $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
        });

        // Migrate existing supplier_tag data to taggables
        DB::statement("
            INSERT INTO taggables (tag_id, taggable_id, taggable_type, created_at, updated_at)
            SELECT 
                tag_id, 
                supplier_id as taggable_id, 
                'App\\\\Models\\\\Supplier' as taggable_type,
                NOW() as created_at,
                NOW() as updated_at
            FROM supplier_tag
        ");

        // Drop old supplier_tag table
        Schema::dropIfExists('supplier_tag');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate supplier_tag table
        Schema::create('supplier_tag', function (Blueprint $table) {
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
        });

        // Migrate data back from taggables to supplier_tag
        DB::statement("
            INSERT INTO supplier_tag (supplier_id, tag_id)
            SELECT taggable_id as supplier_id, tag_id
            FROM taggables
            WHERE taggable_type = 'App\\\\Models\\\\Supplier'
        ");

        // Drop taggables table
        Schema::dropIfExists('taggables');
    }
};
