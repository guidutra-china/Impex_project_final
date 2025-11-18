<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Product;
use App\Models\Component;
use App\Models\CategoryFeature;
use App\Models\ProductFeature;
use App\Models\Currency;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@impex.com',
        ]);

        $this->command->info('✓ Created admin user');

        // Create currencies
        $this->createCurrencies();
        $this->command->info('✓ Created currencies');

        // Create categories
        $categories = Category::factory(8)->create();
        $this->command->info('✓ Created ' . $categories->count() . ' categories');

        // Create tags
        $tags = Tag::factory(10)->create();
        $this->command->info('✓ Created ' . $tags->count() . ' tags');

        // Create clients with unique codes
        $clients = collect();
        $usedCodes = [];
        
        for ($i = 0; $i < 15; $i++) {
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

        // Create suppliers with tags and categories
        $suppliers = Supplier::factory(20)->create();
        
        foreach ($suppliers as $supplier) {
            // Attach random tags (1-4 tags per supplier)
            $supplier->tags()->attach(
                $tags->random(rand(1, 4))->pluck('id')
            );
            
            // Attach random categories (1-3 categories per supplier)
            $supplier->categories()->attach(
                $categories->random(rand(1, 3))->pluck('id')
            );
        }
        
        $this->command->info('✓ Created ' . $suppliers->count() . ' suppliers with tags and categories');

        // Create components
        $components = Component::factory(30)->create();
        $this->command->info('✓ Created ' . $components->count() . ' components');

        // Create category features
        foreach ($categories as $category) {
            CategoryFeature::factory(rand(2, 5))->create([
                'category_id' => $category->id,
            ]);
        }
        
        $this->command->info('✓ Created category features');

        // Create products with features and components
        $products = Product::factory(50)->create();
        
        foreach ($products as $product) {
            // Add features based on category
            if ($product->category) {
                $categoryFeatures = $product->category->features;
                
                foreach ($categoryFeatures as $feature) {
                    if ($feature->required || rand(0, 1)) {
                        $value = $feature->type === 'select' && $feature->options
                            ? json_decode($feature->options)[array_rand(json_decode($feature->options))]
                            : fake()->word();
                        
                        ProductFeature::create([
                            'product_id' => $product->id,
                            'category_feature_id' => $feature->id,
                            'value' => $value,
                        ]);
                    }
                }
            }
            
            // Attach random components (2-8 components per product)
            $product->components()->attach(
                $components->random(rand(2, 8))->mapWithKeys(function ($component) {
                    return [$component->id => ['quantity' => rand(1, 10)]];
                })
            );
        }
        
        $this->command->info('✓ Created ' . $products->count() . ' products with features and components');

        $this->command->info('');
        $this->command->info('🎉 Database seeding completed successfully!');
        $this->command->info('');
        $this->command->info('Summary:');
        $this->command->info('  - Users: ' . User::count());
        $this->command->info('  - Clients: ' . Client::count());
        $this->command->info('  - Suppliers: ' . Supplier::count());
        $this->command->info('  - Categories: ' . Category::count());
        $this->command->info('  - Tags: ' . Tag::count());
        $this->command->info('  - Products: ' . Product::count());
        $this->command->info('  - Components: ' . Component::count());
        $this->command->info('  - Category Features: ' . CategoryFeature::count());
        $this->command->info('  - Product Features: ' . ProductFeature::count());
    }

    private function createCurrencies(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£'],
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥'],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥'],
            ['code' => 'BRL', 'name' => 'Brazilian Real', 'symbol' => 'R$'],
        ];

        foreach ($currencies as $currency) {
            Currency::firstOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }
    }
}
