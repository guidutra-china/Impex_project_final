<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Product;
use App\Models\BomItem;
use App\Models\CategoryFeature;
use App\Models\ProductFeature;
use App\Models\Currency;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Optimized for development with maximum 5 items per table.
     */
    public function run(): void
    {
        // Create admin user using firstOrCreate to avoid duplicate entry errors
        User::firstOrCreate(
            ['email' => 'admin@impex.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('12345678'),
            ]
        );

        $this->command->info('✓ Created admin user');

        // Create currencies
        $this->createCurrencies();
        $this->command->info('✓ Created currencies');

        // Create financial categories
        $this->call(FinancialCategorySeeder::class);

        // Create categories (max 5)
        $categories = Category::factory(5)->create();
        $this->command->info('✓ Created ' . $categories->count() . ' categories');

        // Create tags (max 5)
        $tags = Tag::factory(5)->create();
        $this->command->info('✓ Created ' . $tags->count() . ' tags');

        // Create clients with unique codes (max 5)
        $clients = collect();
        $usedCodes = [];
        
        for ($i = 0; $i < 5; $i++) {
            $client = Client::factory()->make();
            
            // Ensure unique code
            $code = $client->code;
            $counter = 1;
            while (in_array($code, $usedCodes)) {
                $code = strtoupper(substr($client->name, 0, 2)) . $counter;
                $counter++;
            }
            
            $client->code = $code;
            $usedCodes[] = $code;
            $client->save();
            $clients->push($client);
        }
        
        $this->command->info('✓ Created ' . $clients->count() . ' clients');

        // Create client contacts (1-2 contacts per client)
        foreach ($clients as $client) {
            ClientContact::factory(rand(1, 2))->create([
                'client_id' => $client->id,
            ]);
        }
        
        $this->command->info('✓ Created client contacts');

        // Create suppliers with tags and categories (max 5)
        $suppliers = Supplier::factory(5)->create();
        
        foreach ($suppliers as $supplier) {
            // Attach random tags (1-2 tags per supplier)
            $supplier->tags()->attach(
                $tags->random(rand(1, 2))->pluck('id')
            );
            
            // Attach random categories (1-2 categories per supplier)
            $supplier->categories()->attach(
                $categories->random(rand(1, 2))->pluck('id')
            );
            
            // Create supplier contacts (1-2 contacts per supplier)
            SupplierContact::factory(rand(1, 2))->create([
                'supplier_id' => $supplier->id,
            ]);
        }
        
        $this->command->info('✓ Created ' . $suppliers->count() . ' suppliers with tags, categories, and contacts');

        // Create category features (2-3 per category)
        foreach ($categories as $category) {
            CategoryFeature::factory(rand(2, 3))->create([
                'category_id' => $category->id,
            ]);
        }
        
        $this->command->info('✓ Created category features');

        // Create products (max 5)
        $products = Product::factory(5)->create();
        
        foreach ($products as $index => $product) {
            // Add features based on category
            if ($product->category) {
                $categoryFeatures = $product->category->categoryFeatures;
                
                foreach ($categoryFeatures as $feature) {
                    if ($feature->is_required || rand(0, 1)) {
                        $value = $feature->default_value ?? fake()->word();
                        
                        ProductFeature::create([
                            'product_id' => $product->id,
                            'feature_name' => $feature->feature_name,
                            'feature_value' => $value,
                            'unit' => $feature->unit,
                        ]);
                    }
                }
            }
            
            // Add BOM items (components) to some products
            // Only add BOM to products 2-5 using products 1-4 as components
            if ($index > 0) {
                // Get potential component products (products created before this one)
                $componentProducts = $products->slice(0, $index);
                
                // Add 1-3 BOM items per product
                $numBomItems = rand(1, min(3, $componentProducts->count()));
                $selectedComponents = $componentProducts->random($numBomItems);
                
                foreach ($selectedComponents as $component) {
                    BomItem::create([
                        'product_id' => $product->id,
                        'component_product_id' => $component->id,
                        'quantity' => rand(1, 5),
                        'unit_of_measure' => 'pcs',
                        'waste_factor' => rand(0, 10),
                    ]);
                }
            }
            
            // Attach tags to products (1-2 tags per product)
            $product->tags()->attach(
                $tags->random(rand(1, 2))->pluck('id')
            );
        }
        
        $this->command->info('✓ Created ' . $products->count() . ' products with features, BOM items, and tags');

        $this->command->info('');
        $this->command->info('🎉 Database seeding completed successfully!');
        $this->command->info('');
        $this->command->info('Summary:');
        $this->command->info('  - Users: ' . User::count());
        $this->command->info('  - Clients: ' . Client::count());
        $this->command->info('  - Client Contacts: ' . ClientContact::count());
        $this->command->info('  - Suppliers: ' . Supplier::count());
        $this->command->info('  - Supplier Contacts: ' . SupplierContact::count());
        $this->command->info('  - Categories: ' . Category::count());
        $this->command->info('  - Tags: ' . Tag::count());
        $this->command->info('  - Products: ' . Product::count());
        $this->command->info('  - BOM Items: ' . BomItem::count());
        $this->command->info('  - Category Features: ' . CategoryFeature::count());
        $this->command->info('  - Product Features: ' . ProductFeature::count());
    }

    private function createCurrencies(): void
    {
        // Keep only 5 most common currencies
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'name_plural' => 'US Dollars', 'symbol' => '$'],
            ['code' => 'EUR', 'name' => 'Euro', 'name_plural' => 'Euros', 'symbol' => '€'],
            ['code' => 'GBP', 'name' => 'British Pound', 'name_plural' => 'British Pounds', 'symbol' => '£'],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'name_plural' => 'Chinese Yuan', 'symbol' => '¥'],
            ['code' => 'BRL', 'name' => 'Brazilian Real', 'name_plural' => 'Brazilian Reais', 'symbol' => 'R$'],
        ];

        foreach ($currencies as $currency) {
            Currency::firstOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }
    }
}
