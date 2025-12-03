<?php

namespace Tests\Integration\Repositories;

use App\Models\Category;
use App\Models\User;
use App\Repositories\CategoryRepository;
use Tests\TestCase;

class CategoryRepositoryTest extends TestCase
{
    private CategoryRepository $repository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = app(CategoryRepository::class);
        $this->user = User::factory()->create();
    }

    // ===== TESTES CRUD =====

    /** @test */
    public function it_can_find_category_by_id()
    {
        $category = Category::factory()->for($this->user)->create();
        
        $found = $this->repository->findById($category->id);
        
        expect($found)->not->toBeNull();
        expect($found->id)->toBe($category->id);
    }

    /** @test */
    public function it_returns_null_when_category_not_found()
    {
        $found = $this->repository->findById(99999);
        
        expect($found)->toBeNull();
    }

    /** @test */
    public function it_can_get_all_categories()
    {
        Category::factory(3)->for($this->user)->create();
        
        $categories = $this->repository->all();
        
        expect($categories->count())->toBeGreaterThanOrEqual(3);
    }

    /** @test */
    public function it_can_create_category()
    {
        $data = [
            'name' => 'Test Category',
            'description' => 'Test Description',
            'status' => 'active',
            'created_by' => $this->user->id,
        ];
        
        $category = $this->repository->create($data);
        
        expect($category)->toBeInstanceOf(Category::class);
        expect($category->name)->toBe('Test Category');
        expect($category->status)->toBe('active');
    }

    /** @test */
    public function it_can_update_category()
    {
        $category = Category::factory()->for($this->user)->create();
        
        $updated = $this->repository->update($category->id, [
            'name' => 'Updated Category',
            'status' => 'inactive',
        ]);
        
        expect($updated)->toBeTrue();
        expect($category->fresh()->name)->toBe('Updated Category');
        expect($category->fresh()->status)->toBe('inactive');
    }

    /** @test */
    public function it_can_delete_category()
    {
        $category = Category::factory()->for($this->user)->create();
        
        $deleted = $this->repository->delete($category->id);
        
        expect($deleted)->toBeTrue();
        expect(Category::find($category->id))->toBeNull();
    }

    // ===== TESTES DE FILTROS =====

    /** @test */
    public function it_can_get_categories_by_status()
    {
        Category::factory(2)->for($this->user)->create(['status' => 'active']);
        Category::factory(1)->for($this->user)->create(['status' => 'inactive']);
        
        $active = $this->repository->getByStatus('active');
        
        expect($active->count())->toBeGreaterThanOrEqual(2);
        expect($active->every(fn($c) => $c->status === 'active'))->toBeTrue();
    }

    // ===== TESTES DE QUERIES ESPECÍFICAS =====

    /** @test */
    public function it_can_get_features_query()
    {
        $category = Category::factory()->for($this->user)->create();
        
        $query = $this->repository->getFeaturesQuery($category->id);
        
        expect($query)->not->toBeNull();
    }

    /** @test */
    public function it_can_get_select_options()
    {
        Category::factory(3)->for($this->user)->create(['status' => 'active']);
        
        $options = $this->repository->getSelectOptions();
        
        expect($options)->toBeArray();
        expect(count($options))->toBeGreaterThanOrEqual(3);
    }

    // ===== TESTES DE BUSCA =====

    /** @test */
    public function it_can_search_categories()
    {
        $category = Category::factory()
            ->for($this->user)
            ->create(['name' => 'UNIQUE-CATEGORY-12345']);
        
        $results = $this->repository->searchCategories('UNIQUE-CATEGORY');
        
        expect($results->pluck('id')->contains($category->id))->toBeTrue();
    }

    // ===== TESTES DE ATIVAÇÃO/DESATIVAÇÃO =====

    /** @test */
    public function it_can_activate_category()
    {
        $category = Category::factory()
            ->for($this->user)
            ->create(['status' => 'inactive']);
        
        $result = $this->repository->activate($category->id);
        
        expect($result)->toBeTrue();
        expect($category->fresh()->status)->toBe('active');
    }

    /** @test */
    public function it_can_deactivate_category()
    {
        $category = Category::factory()
            ->for($this->user)
            ->create(['status' => 'active']);
        
        $result = $this->repository->deactivate($category->id);
        
        expect($result)->toBeTrue();
        expect($category->fresh()->status)->toBe('inactive');
    }

    // ===== TESTES DE EDGE CASES =====

    /** @test */
    public function it_handles_empty_results_gracefully()
    {
        $results = $this->repository->getByStatus('non_existent_status');
        
        expect($results)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
        expect($results->count())->toBe(0);
    }

    /** @test */
    public function it_can_count_categories_by_status()
    {
        Category::factory(3)->for($this->user)->create(['status' => 'active']);
        
        $count = $this->repository->countByStatus('active');
        
        expect($count)->toBeGreaterThanOrEqual(3);
    }

    /** @test */
    public function it_can_get_query_builder()
    {
        $query = $this->repository->getQuery();
        
        expect($query)->not->toBeNull();
        expect($query->count())->toBeGreaterThanOrEqual(0);
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $this->expectException(\Exception::class);
        
        $this->repository->create([
            'description' => 'Test',
        ]);
    }

    /** @test */
    public function it_can_get_active_categories()
    {
        Category::factory(2)->for($this->user)->create(['status' => 'active']);
        Category::factory(1)->for($this->user)->create(['status' => 'inactive']);
        
        $active = $this->repository->getActive();
        
        expect($active->count())->toBeGreaterThanOrEqual(2);
        expect($active->every(fn($c) => $c->status === 'active'))->toBeTrue();
    }
}
