<?php

namespace Tests\Integration\Repositories;

use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\User;
use App\Repositories\ProductRepository;
use Tests\TestCase;

class ProductRepositoryTest extends TestCase
{
    private ProductRepository $repository;
    private Category $category;
    private Supplier $supplier;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = app(ProductRepository::class);
        
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
        $this->supplier = Supplier::factory()->for($this->user)->create();
    }

    /** @test */
    public function it_can_find_product_by_id()
    {
        $product = Product::factory()
            ->for($this->category)
            ->for($this->supplier)
            ->create();
        
        $found = $this->repository->findById($product->id);
        
        expect($found)->not->toBeNull();
        expect($found->id)->toBe($product->id);
    }

    /** @test */
    public function it_can_find_product_by_sku()
    {
        $product = Product::factory()
            ->for($this->category)
            ->for($this->supplier)
            ->create(['sku' => 'TEST-SKU-001']);
        
        $found = $this->repository->findBySku('TEST-SKU-001');
        
        expect($found)->not->toBeNull();
        expect($found->sku)->toBe('TEST-SKU-001');
    }

    /** @test */
    public function it_returns_null_when_product_not_found()
    {
        $found = $this->repository->findById(99999);
        
        expect($found)->toBeNull();
    }

    /** @test */
    public function it_can_get_all_products()
    {
        Product::factory(3)
            ->for($this->category)
            ->for($this->supplier)
            ->create();
        
        $products = $this->repository->all();
        
        expect($products)->toHaveCount(3);
    }

    /** @test */
    public function it_can_create_product()
    {
        $data = [
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'price' => 10000,
            'status' => 'active',
        ];
        
        $product = $this->repository->create($data);
        
        expect($product)->toBeInstanceOf(Product::class);
        expect($product->name)->toBe('Test Product');
        expect($product->sku)->toBe('TEST-001');
    }

    /** @test */
    public function it_can_update_product()
    {
        $product = Product::factory()
            ->for($this->category)
            ->for($this->supplier)
            ->create();
        
        $updated = $this->repository->update($product->id, [
            'name' => 'Updated Product',
            'price' => 20000,
        ]);
        
        expect($updated)->toBeTrue();
        expect($product->fresh()->name)->toBe('Updated Product');
        expect($product->fresh()->price)->toBe(20000);
    }

    /** @test */
    public function it_can_delete_product()
    {
        $product = Product::factory()
            ->for($this->category)
            ->for($this->supplier)
            ->create();
        
        $deleted = $this->repository->delete($product->id);
        
        expect($deleted)->toBeTrue();
        expect(Product::find($product->id))->toBeNull();
    }

    /** @test */
    public function it_can_get_active_products()
    {
        Product::factory(2)
            ->for($this->category)
            ->for($this->supplier)
            ->create(['status' => 'active']);
        Product::factory(1)
            ->for($this->category)
            ->for($this->supplier)
            ->create(['status' => 'inactive']);
        
        $active = $this->repository->getActiveProducts();
        
        expect($active->count())->toBeGreaterThanOrEqual(2);
        expect($active->every(fn($p) => $p->status === 'active'))->toBeTrue();
    }

    /** @test */
    public function it_can_get_products_by_category()
    {
        Product::factory(3)
            ->for($this->category)
            ->for($this->supplier)
            ->create();
        
        $products = $this->repository->getProductsByCategory($this->category->id);
        
        expect($products)->toHaveCount(3);
        expect($products->every(fn($p) => $p->category_id === $this->category->id))->toBeTrue();
    }

    /** @test */
    public function it_can_get_products_by_supplier()
    {
        Product::factory(3)
            ->for($this->category)
            ->for($this->supplier)
            ->create();
        
        $products = $this->repository->getProductsBySupplier($this->supplier->id);
        
        expect($products->count())->toBeGreaterThanOrEqual(3);
        expect($products->every(fn($p) => $p->supplier_id === $this->supplier->id))->toBeTrue();
    }

    /** @test */
    public function it_can_search_products()
    {
        $product = Product::factory()
            ->for($this->category)
            ->for($this->supplier)
            ->create(['name' => 'Unique Product Name']);
        
        $results = $this->repository->searchProducts('Unique Product');
        
        expect($results->pluck('id')->contains($product->id))->toBeTrue();
    }

    /** @test */
    public function it_can_get_products_above_price()
    {
        Product::factory(2)
            ->for($this->category)
            ->for($this->supplier)
            ->create(['price' => 20000]);
        Product::factory(1)
            ->for($this->category)
            ->for($this->supplier)
            ->create(['price' => 5000]);
        
        $expensive = $this->repository->getProductsAbovePrice(15000);
        
        expect($expensive->count())->toBeGreaterThanOrEqual(2);
        expect($expensive->every(fn($p) => $p->price >= 15000))->toBeTrue();
    }

    /** @test */
    public function it_can_count_products_by_category()
    {
        Product::factory(4)
            ->for($this->category)
            ->for($this->supplier)
            ->create();
        
        $count = $this->repository->countByCategory($this->category->id);
        
        expect($count)->toBe(4);
    }

    /** @test */
    public function it_can_count_products_by_supplier()
    {
        Product::factory(5)
            ->for($this->category)
            ->for($this->supplier)
            ->create();
        
        $count = $this->repository->countBySupplier($this->supplier->id);
        
        expect($count)->toBeGreaterThanOrEqual(5);
    }

    /** @test */
    public function it_can_get_average_price()
    {
        Product::factory()
            ->for($this->category)
            ->for($this->supplier)
            ->create(['price' => 10000]);
        Product::factory()
            ->for($this->category)
            ->for($this->supplier)
            ->create(['price' => 20000]);
        
        $average = $this->repository->getAveragePrice();
        
        expect($average)->toBeGreaterThan(0);
    }

    /** @test */
    public function it_can_get_statistics()
    {
        Product::factory(5)
            ->for($this->category)
            ->for($this->supplier)
            ->create();
        
        $stats = $this->repository->getStatistics();
        
        expect($stats)->toHaveKeys(['total_products', 'active_products', 'products_with_bom', 'average_price', 'total_inventory_value']);
        expect($stats['total_products'])->toBeGreaterThanOrEqual(5);
    }
}
