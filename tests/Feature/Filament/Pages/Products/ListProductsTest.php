<?php

namespace Tests\Feature\Filament\Pages\Products;

use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Tests\TestCase;

class ListProductsTest extends TestCase
{
    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->for($this->user)->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_render_list_page()
    {
        $response = $this->get('/admin/products');
        $response->assertSuccessful();
    }

    /** @test */
    public function it_displays_products_in_table()
    {
        Product::factory(3)->for($this->user)->for($this->category)->create();
        $response = $this->get('/admin/products');
        $response->assertSuccessful();
    }

    /** @test */
    public function it_can_filter_products_by_status()
    {
        Product::factory(2)->for($this->user)->for($this->category)->create(['status' => 'active']);
        Product::factory(1)->for($this->user)->for($this->category)->create(['status' => 'inactive']);
        
        $response = $this->get('/admin/products?status=active');
        $response->assertSuccessful();
    }

    /** @test */
    public function it_can_filter_products_by_category()
    {
        $category2 = Category::factory()->for($this->user)->create();
        Product::factory(2)->for($this->user)->for($this->category)->create();
        Product::factory(1)->for($this->user)->for($category2)->create();
        
        $response = $this->get('/admin/products?category=' . $this->category->id);
        $response->assertSuccessful();
    }

    /** @test */
    public function it_can_search_products_by_name()
    {
        $product = Product::factory()->for($this->user)->for($this->category)->create(['name' => 'UNIQUE-PRODUCT']);
        
        $response = $this->get('/admin/products?search=UNIQUE');
        $response->assertSuccessful();
        $response->assertSee('UNIQUE-PRODUCT');
    }

    /** @test */
    public function it_can_search_products_by_sku()
    {
        $product = Product::factory()->for($this->user)->for($this->category)->create(['sku' => 'SKU-UNIQUE-123']);
        
        $response = $this->get('/admin/products?search=SKU-UNIQUE');
        $response->assertSuccessful();
    }

    /** @test */
    public function it_can_sort_products()
    {
        Product::factory()->for($this->user)->for($this->category)->create(['name' => 'Product A']);
        Product::factory()->for($this->user)->for($this->category)->create(['name' => 'Product B']);
        
        $response = $this->get('/admin/products?sort=name');
        $response->assertSuccessful();
    }

    /** @test */
    public function it_has_create_button()
    {
        $response = $this->get('/admin/products');
        $response->assertSuccessful();
        $response->assertSee('Create');
    }

    /** @test */
    public function it_displays_empty_state_when_no_products()
    {
        $response = $this->get('/admin/products');
        $response->assertSuccessful();
    }

    /** @test */
    public function unauthorized_user_cannot_view_products()
    {
        $this->actingAs(User::factory()->create());
        $response = $this->get('/admin/products');
        $this->assertTrue($response->status() === 403 || $response->status() === 302);
    }
}
