<?php

use App\Models\BomItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductFeature;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can duplicate a product without BOM items', function () {
    // Create a category for the product
    $category = Category::factory()->create();
    
    // Create a product
    $originalProduct = Product::factory()->create([
        'name' => 'Original Product',
        'sku' => 'ORIG-001',
        'category_id' => $category->id,
        'price' => 10000, // $100.00
    ]);

    // Duplicate the product
    $duplicatedProduct = $originalProduct->duplicate();

    // Assert the duplicated product exists
    expect($duplicatedProduct)->toBeInstanceOf(Product::class);
    expect($duplicatedProduct->id)->not->toBe($originalProduct->id);
    expect($duplicatedProduct->name)->toBe('Original Product (Copy)');
    expect($duplicatedProduct->sku)->toBeNull();
    expect($duplicatedProduct->price)->toBe(10000);
    expect($duplicatedProduct->category_id)->toBe($category->id);
});

it('can duplicate a product with BOM items', function () {
    // Create category and component products
    $category = Category::factory()->create();
    $component1 = Product::factory()->create(['name' => 'Component 1', 'price' => 5000]);
    $component2 = Product::factory()->create(['name' => 'Component 2', 'price' => 3000]);

    // Create the main product
    $originalProduct = Product::factory()->create([
        'name' => 'Product with BOM',
        'category_id' => $category->id,
    ]);

    // Create BOM items
    BomItem::create([
        'product_id' => $originalProduct->id,
        'component_product_id' => $component1->id,
        'quantity' => 2,
        'unit_of_measure' => 'pcs',
        'waste_factor' => 5,
        'sort_order' => 1,
    ]);

    BomItem::create([
        'product_id' => $originalProduct->id,
        'component_product_id' => $component2->id,
        'quantity' => 3,
        'unit_of_measure' => 'pcs',
        'waste_factor' => 10,
        'sort_order' => 2,
    ]);

    // Duplicate the product
    $duplicatedProduct = $originalProduct->duplicate();

    // Assert the duplicated product has BOM items
    expect($duplicatedProduct->bomItems)->toHaveCount(2);
    
    $duplicatedBomItems = $duplicatedProduct->bomItems->sortBy('sort_order')->values();
    
    expect($duplicatedBomItems[0]->component_product_id)->toBe($component1->id);
    expect($duplicatedBomItems[0]->quantity)->toBe('2.0000');
    expect($duplicatedBomItems[0]->unit_of_measure)->toBe('pcs');
    
    expect($duplicatedBomItems[1]->component_product_id)->toBe($component2->id);
    expect($duplicatedBomItems[1]->quantity)->toBe('3.0000');
    expect($duplicatedBomItems[1]->unit_of_measure)->toBe('pcs');
});

it('can duplicate a product with features', function () {
    $category = Category::factory()->create();
    
    $originalProduct = Product::factory()->create([
        'name' => 'Product with Features',
        'category_id' => $category->id,
    ]);

    // Create features
    ProductFeature::create([
        'product_id' => $originalProduct->id,
        'feature_name' => 'Color',
        'feature_value' => 'Red',
        'sort_order' => 1,
    ]);

    ProductFeature::create([
        'product_id' => $originalProduct->id,
        'feature_name' => 'Size',
        'feature_value' => 'Large',
        'sort_order' => 2,
    ]);

    // Duplicate the product
    $duplicatedProduct = $originalProduct->duplicate();

    // Assert the duplicated product has features
    expect($duplicatedProduct->features)->toHaveCount(2);
    
    $duplicatedFeatures = $duplicatedProduct->features->sortBy('sort_order')->values();
    
    expect($duplicatedFeatures[0]->feature_name)->toBe('Color');
    expect($duplicatedFeatures[0]->feature_value)->toBe('Red');
    
    expect($duplicatedFeatures[1]->feature_name)->toBe('Size');
    expect($duplicatedFeatures[1]->feature_value)->toBe('Large');
});

it('can duplicate a product with tags', function () {
    $category = Category::factory()->create();
    
    $originalProduct = Product::factory()->create([
        'name' => 'Product with Tags',
        'category_id' => $category->id,
    ]);

    // Create and attach tags
    $tag1 = Tag::factory()->create(['name' => 'Premium']);
    $tag2 = Tag::factory()->create(['name' => 'Bestseller']);
    
    $originalProduct->tags()->attach([$tag1->id, $tag2->id]);

    // Duplicate the product
    $duplicatedProduct = $originalProduct->duplicate();

    // Assert the duplicated product has tags
    expect($duplicatedProduct->tags)->toHaveCount(2);
    expect($duplicatedProduct->tags->pluck('name')->toArray())->toContain('Premium', 'Bestseller');
});

it('duplicates all product data correctly', function () {
    $category = Category::factory()->create();
    
    $originalProduct = Product::factory()->create([
        'name' => 'Complete Product',
        'sku' => 'COMP-001',
        'category_id' => $category->id,
        'price' => 15000,
        'moq' => 100,
        'moq_unit' => 'pcs',
        'lead_time_days' => 30,
        'brand' => 'Test Brand',
        'model_number' => 'MODEL-123',
        'hs_code' => '1234.56.78',
        'origin_country' => 'China',
        'net_weight' => 1.5,
        'gross_weight' => 2.0,
        'packing_notes' => 'Handle with care',
        'internal_notes' => 'Internal note',
    ]);

    // Duplicate the product
    $duplicatedProduct = $originalProduct->duplicate();

    // Assert all data is copied correctly
    expect($duplicatedProduct->name)->toBe('Complete Product (Copy)');
    expect($duplicatedProduct->sku)->toBeNull(); // SKU should be cleared
    expect($duplicatedProduct->price)->toBe(15000);
    expect($duplicatedProduct->moq)->toBe(100);
    expect($duplicatedProduct->moq_unit)->toBe('pcs');
    expect($duplicatedProduct->lead_time_days)->toBe(30);
    expect($duplicatedProduct->brand)->toBe('Test Brand');
    expect($duplicatedProduct->model_number)->toBe('MODEL-123');
    expect($duplicatedProduct->hs_code)->toBe('1234.56.78');
    expect($duplicatedProduct->origin_country)->toBe('China');
    expect((float) $duplicatedProduct->net_weight)->toBe(1.5);
    expect((float) $duplicatedProduct->gross_weight)->toBe(2.0);
    expect($duplicatedProduct->packing_notes)->toBe('Handle with care');
    expect($duplicatedProduct->internal_notes)->toBe('Internal note');
});

it('recalculates manufacturing costs after duplication', function () {
    $category = Category::factory()->create();
    $component = Product::factory()->create(['price' => 5000]);

    $originalProduct = Product::factory()->create([
        'name' => 'Product for Cost Test',
        'category_id' => $category->id,
        'direct_labor_cost' => 2000,
        'direct_overhead_cost' => 1000,
        'markup_percentage' => 20,
    ]);

    // Create BOM item
    BomItem::create([
        'product_id' => $originalProduct->id,
        'component_product_id' => $component->id,
        'quantity' => 2,
        'unit_of_measure' => 'pcs',
        'waste_factor' => 0,
        'sort_order' => 1,
    ]);

    // Refresh to get updated costs
    $originalProduct->refresh();

    // Duplicate the product
    $duplicatedProduct = $originalProduct->duplicate();

    // Assert costs are recalculated
    expect($duplicatedProduct->bom_material_cost)->toBeGreaterThan(0);
    expect($duplicatedProduct->total_manufacturing_cost)->toBeGreaterThan(0);
    expect($duplicatedProduct->calculated_selling_price)->toBeGreaterThan(0);
});

// NEW TESTS FOR SELECTIVE DUPLICATION

it('can duplicate a product without BOM items when option is disabled', function () {
    $category = Category::factory()->create();
    $component = Product::factory()->create(['price' => 5000]);

    $originalProduct = Product::factory()->create([
        'name' => 'Product with BOM',
        'category_id' => $category->id,
    ]);

    // Create BOM item
    BomItem::create([
        'product_id' => $originalProduct->id,
        'component_product_id' => $component->id,
        'quantity' => 2,
        'unit_of_measure' => 'pcs',
        'waste_factor' => 0,
        'sort_order' => 1,
    ]);

    // Duplicate WITHOUT BOM items
    $duplicatedProduct = $originalProduct->duplicate(['bom_items' => false]);

    // Assert the duplicated product has NO BOM items
    expect($duplicatedProduct->bomItems)->toHaveCount(0);
});

it('can duplicate a product without features when option is disabled', function () {
    $category = Category::factory()->create();
    
    $originalProduct = Product::factory()->create([
        'name' => 'Product with Features',
        'category_id' => $category->id,
    ]);

    // Create features
    ProductFeature::create([
        'product_id' => $originalProduct->id,
        'feature_name' => 'Color',
        'feature_value' => 'Red',
        'sort_order' => 1,
    ]);

    // Duplicate WITHOUT features
    $duplicatedProduct = $originalProduct->duplicate(['features' => false]);

    // Assert the duplicated product has NO features
    expect($duplicatedProduct->features)->toHaveCount(0);
});

it('can duplicate a product without tags when option is disabled', function () {
    $category = Category::factory()->create();
    
    $originalProduct = Product::factory()->create([
        'name' => 'Product with Tags',
        'category_id' => $category->id,
    ]);

    // Create and attach tags
    $tag1 = Tag::factory()->create(['name' => 'Premium']);
    $tag2 = Tag::factory()->create(['name' => 'Bestseller']);
    
    $originalProduct->tags()->attach([$tag1->id, $tag2->id]);

    // Duplicate WITHOUT tags
    $duplicatedProduct = $originalProduct->duplicate(['tags' => false]);

    // Assert the duplicated product has NO tags
    expect($duplicatedProduct->tags)->toHaveCount(0);
});

it('can duplicate a product with selective options', function () {
    $category = Category::factory()->create();
    $component = Product::factory()->create(['price' => 5000]);
    
    $originalProduct = Product::factory()->create([
        'name' => 'Complete Product',
        'category_id' => $category->id,
    ]);

    // Create BOM item
    BomItem::create([
        'product_id' => $originalProduct->id,
        'component_product_id' => $component->id,
        'quantity' => 2,
        'unit_of_measure' => 'pcs',
        'waste_factor' => 0,
        'sort_order' => 1,
    ]);

    // Create features
    ProductFeature::create([
        'product_id' => $originalProduct->id,
        'feature_name' => 'Color',
        'feature_value' => 'Red',
        'sort_order' => 1,
    ]);

    // Create and attach tags
    $tag = Tag::factory()->create(['name' => 'Premium']);
    $originalProduct->tags()->attach($tag->id);

    // Duplicate with ONLY BOM items and features (no tags)
    $duplicatedProduct = $originalProduct->duplicate([
        'bom_items' => true,
        'features' => true,
        'tags' => false,
        'avatar' => false,
    ]);

    // Assert selective duplication
    expect($duplicatedProduct->bomItems)->toHaveCount(1);
    expect($duplicatedProduct->features)->toHaveCount(1);
    expect($duplicatedProduct->tags)->toHaveCount(0);
});

it('uses default options when no options are provided', function () {
    $category = Category::factory()->create();
    $component = Product::factory()->create(['price' => 5000]);
    
    $originalProduct = Product::factory()->create([
        'name' => 'Product with Everything',
        'category_id' => $category->id,
    ]);

    // Create BOM item
    BomItem::create([
        'product_id' => $originalProduct->id,
        'component_product_id' => $component->id,
        'quantity' => 2,
        'unit_of_measure' => 'pcs',
        'waste_factor' => 0,
        'sort_order' => 1,
    ]);

    // Create feature
    ProductFeature::create([
        'product_id' => $originalProduct->id,
        'feature_name' => 'Color',
        'feature_value' => 'Red',
        'sort_order' => 1,
    ]);

    // Create and attach tag
    $tag = Tag::factory()->create(['name' => 'Premium']);
    $originalProduct->tags()->attach($tag->id);

    // Duplicate with NO options (should use defaults: duplicate everything)
    $duplicatedProduct = $originalProduct->duplicate();

    // Assert everything is duplicated by default
    expect($duplicatedProduct->bomItems)->toHaveCount(1);
    expect($duplicatedProduct->features)->toHaveCount(1);
    expect($duplicatedProduct->tags)->toHaveCount(1);
});
