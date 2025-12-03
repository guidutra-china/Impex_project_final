<?php

namespace Tests\Integration\Repositories;

use App\Models\Supplier;
use App\Models\User;
use App\Repositories\SupplierRepository;
use Tests\TestCase;

class SupplierRepositoryTest extends TestCase
{
    private SupplierRepository $repository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = app(SupplierRepository::class);
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_find_supplier_by_id()
    {
        $supplier = Supplier::factory()->for($this->user)->create();
        
        $found = $this->repository->findById($supplier->id);
        
        expect($found)->not->toBeNull();
        expect($found->id)->toBe($supplier->id);
    }

    /** @test */
    public function it_returns_null_when_supplier_not_found()
    {
        $found = $this->repository->findById(99999);
        
        expect($found)->toBeNull();
    }

    /** @test */
    public function it_can_get_all_suppliers()
    {
        Supplier::factory(3)->for($this->user)->create();
        
        $suppliers = $this->repository->all();
        
        expect($suppliers)->toHaveCount(3);
    }

    /** @test */
    public function it_can_create_supplier()
    {
        $data = [
            'user_id' => $this->user->id,
            'name' => 'Test Supplier',
            'code' => 'SUP001',
            'email' => 'supplier@test.com',
            'country' => 'China',
            'status' => 'active',
        ];
        
        $supplier = $this->repository->create($data);
        
        expect($supplier)->toBeInstanceOf(Supplier::class);
        expect($supplier->name)->toBe('Test Supplier');
        expect($supplier->code)->toBe('SUP001');
    }

    /** @test */
    public function it_can_update_supplier()
    {
        $supplier = Supplier::factory()->for($this->user)->create();
        
        $updated = $this->repository->update($supplier->id, [
            'name' => 'Updated Supplier',
            'status' => 'inactive',
        ]);
        
        expect($updated)->toBeTrue();
        expect($supplier->fresh()->name)->toBe('Updated Supplier');
        expect($supplier->fresh()->status)->toBe('inactive');
    }

    /** @test */
    public function it_can_delete_supplier()
    {
        $supplier = Supplier::factory()->for($this->user)->create();
        
        $deleted = $this->repository->delete($supplier->id);
        
        expect($deleted)->toBeTrue();
        expect(Supplier::find($supplier->id))->toBeNull();
    }

    /** @test */
    public function it_can_get_active_suppliers()
    {
        Supplier::factory(2)->for($this->user)->create(['status' => 'active']);
        Supplier::factory(1)->for($this->user)->create(['status' => 'inactive']);
        
        $active = $this->repository->getActiveSuppliers();
        
        expect($active->count())->toBeGreaterThanOrEqual(2);
        expect($active->every(fn($s) => $s->status === 'active'))->toBeTrue();
    }

    /** @test */
    public function it_can_get_suppliers_by_country()
    {
        Supplier::factory(3)->for($this->user)->create(['country' => 'China']);
        
        $suppliers = $this->repository->getSuppliersByCountry('China');
        
        expect($suppliers->count())->toBeGreaterThanOrEqual(3);
        expect($suppliers->every(fn($s) => $s->country === 'China'))->toBeTrue();
    }

    /** @test */
    public function it_can_search_suppliers()
    {
        $supplier = Supplier::factory()->for($this->user)->create(['name' => 'Unique Supplier Name']);
        
        $results = $this->repository->searchSuppliers('Unique Supplier');
        
        expect($results->pluck('id')->contains($supplier->id))->toBeTrue();
    }

    /** @test */
    public function it_can_count_suppliers_by_country()
    {
        Supplier::factory(4)->for($this->user)->create(['country' => 'Vietnam']);
        
        $count = $this->repository->countByCountry('Vietnam');
        
        expect($count)->toBe(4);
    }

    /** @test */
    public function it_can_count_suppliers_by_status()
    {
        Supplier::factory(3)->for($this->user)->create(['status' => 'active']);
        
        $count = $this->repository->countByStatus('active');
        
        expect($count)->toBeGreaterThanOrEqual(3);
    }

    /** @test */
    public function it_can_get_statistics()
    {
        Supplier::factory(5)->for($this->user)->create();
        
        $stats = $this->repository->getStatistics();
        
        expect($stats)->toHaveKeys(['total_suppliers', 'active_suppliers', 'suppliers_with_products', 'suppliers_with_quotes']);
        expect($stats['total_suppliers'])->toBeGreaterThanOrEqual(5);
    }
}
