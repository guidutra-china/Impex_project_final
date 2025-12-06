<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Create tags from existing categories (if not already exist)
        $categories = DB::table('categories')->get();
        
        foreach ($categories as $category) {
            // Check if tag with same name already exists
            $existingTag = DB::table('tags')->where('name', $category->name)->first();
            
            if (!$existingTag) {
                DB::table('tags')->insert([
                    'name' => $category->name,
                    'slug' => \Illuminate\Support\Str::slug($category->name),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        // Step 2: Migrate products from category_id to tags
        $products = DB::table('products')->whereNotNull('category_id')->get();
        
        foreach ($products as $product) {
            // Get category name
            $category = DB::table('categories')->where('id', $product->category_id)->first();
            
            if ($category) {
                // Get corresponding tag
                $tag = DB::table('tags')->where('name', $category->name)->first();
                
                if ($tag) {
                    // Check if relationship already exists
                    $exists = DB::table('product_tag')
                        ->where('product_id', $product->id)
                        ->where('tag_id', $tag->id)
                        ->exists();
                    
                    if (!$exists) {
                        // Create product-tag relationship
                        DB::table('product_tag')->insert([
                            'product_id' => $product->id,
                            'tag_id' => $tag->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
        
        // Step 3: Make category_id nullable (don't drop yet, for safety)
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove product-tag relationships that were created from categories
        $categories = DB::table('categories')->get();
        
        foreach ($categories as $category) {
            $tag = DB::table('tags')->where('name', $category->name)->first();
            
            if ($tag) {
                DB::table('product_tag')->where('tag_id', $tag->id)->delete();
            }
        }
        
        // Make category_id required again
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable(false)->change();
        });
    }
};
