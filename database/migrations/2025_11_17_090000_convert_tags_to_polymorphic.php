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
            $table->id();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->morphs('taggable'); // Creates taggable_id and taggable_type
            $table->timestamps();
            
            $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
        });

        // Migrate existing supplier_tag data to taggables
        // Check if supplier_tag table exists and has data
        if (Schema::hasTable('supplier_tag')) {
            $supplierTags = DB::table('supplier_tag')->get();
            
            foreach ($supplierTags as $supplierTag) {
                DB::table('taggables')->insert([
                    'tag_id' => $supplierTag->tag_id,
                    'taggable_id' => $supplierTag->supplier_id,
                    'taggable_type' => 'App\\Models\\Supplier',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            // Drop old supplier_tag table
            Schema::dropIfExists('supplier_tag');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate supplier_tag table
        Schema::create('supplier_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
        });

        // Migrate data back from taggables to supplier_tag
        $supplierTaggables = DB::table('taggables')
            ->where('taggable_type', 'App\\Models\\Supplier')
            ->get();
        
        foreach ($supplierTaggables as $taggable) {
            DB::table('supplier_tag')->insert([
                'supplier_id' => $taggable->taggable_id,
                'tag_id' => $taggable->tag_id,
            ]);
        }

        // Drop taggables table
        Schema::dropIfExists('taggables');
    }
};
