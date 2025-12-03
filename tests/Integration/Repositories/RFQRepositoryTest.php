<?php

namespace Tests\Integration\Repositories;

use App\Models\RFQ;
use App\Models\Product;
use App\Models\User;
use App\Repositories\RFQRepository;
use Tests\TestCase;

class RFQRepositoryTest extends TestCase
{
    private RFQRepository $repository;
    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = app(RFQRepository::class);
        
        $this->user = User::factory()->create();
        $this->product = Product::factory()->for($this->user)->create();
    }

    // ===== TESTES CRUD =====

    /** @test */
    public function it_can_find_rfq_by_id()
    {
        $rfq = RFQ::factory()->for($this->product)->create();
        
        $found = $this->repository->findById($rfq->id);
        
        expect($found)->not->toBeNull();
        expect($found->id)->toBe($rfq->id);
    }

    /** @test */
    public function it_returns_null_when_rfq_not_found()
    {
        $found = $this->repository->findById(99999);
        
        expect($found)->toBeNull();
    }

    /** @test */
    public function it_can_get_all_rfqs()
    {
        RFQ::factory(3)->for($this->product)->create();
        
        $rfqs = $this->repository->all();
        
        expect($rfqs->count())->toBeGreaterThanOrEqual(3);
    }

    /** @test */
    public function it_can_create_rfq()
    {
        $data = [
            'product_id' => $this->product->id,
            'rfq_number' => 'RFQ-' . now()->timestamp,
            'status' => 'draft',
            'quantity' => 100,
            'created_by' => $this->user->id,
        ];
        
        $rfq = $this->repository->create($data);
        
        expect($rfq)->toBeInstanceOf(RFQ::class);
        expect($rfq->status)->toBe('draft');
        expect($rfq->quantity)->toBe(100);
    }

    /** @test */
    public function it_can_update_rfq()
    {
        $rfq = RFQ::factory()->for($this->product)->create();
        
        $updated = $this->repository->update($rfq->id, [
            'status' => 'sent',
            'quantity' => 200,
        ]);
        
        expect($updated)->toBeTrue();
        expect($rfq->fresh()->status)->toBe('sent');
        expect($rfq->fresh()->quantity)->toBe(200);
    }

    /** @test */
    public function it_can_delete_rfq()
    {
        $rfq = RFQ::factory()->for($this->product)->create();
        
        $deleted = $this->repository->delete($rfq->id);
        
        expect($deleted)->toBeTrue();
        expect(RFQ::find($rfq->id))->toBeNull();
    }

    // ===== TESTES DE FILTROS =====

    /** @test */
    public function it_can_get_rfqs_by_status()
    {
        RFQ::factory(2)->for($this->product)->create(['status' => 'draft']);
        RFQ::factory(1)->for($this->product)->create(['status' => 'sent']);
        
        $drafts = $this->repository->getByStatus('draft');
        
        expect($drafts->count())->toBeGreaterThanOrEqual(2);
        expect($drafts->every(fn($r) => $r->status === 'draft'))->toBeTrue();
    }

    /** @test */
    public function it_can_get_rfqs_by_product()
    {
        RFQ::factory(3)->for($this->product)->create();
        
        $rfqs = $this->repository->getByProduct($this->product->id);
        
        expect($rfqs->count())->toBe(3);
        expect($rfqs->every(fn($r) => $r->product_id === $this->product->id))->toBeTrue();
    }

    // ===== TESTES DE BUSCA =====

    /** @test */
    public function it_can_search_rfqs()
    {
        $rfq = RFQ::factory()
            ->for($this->product)
            ->create(['rfq_number' => 'RFQ-UNIQUE-12345']);
        
        $results = $this->repository->searchRFQs('RFQ-UNIQUE');
        
        expect($results->pluck('id')->contains($rfq->id))->toBeTrue();
    }

    // ===== TESTES DE ESTATÍSTICAS =====

    /** @test */
    public function it_can_get_statistics()
    {
        RFQ::factory(5)->for($this->product)->create();
        
        $stats = $this->repository->getStatistics();
        
        expect($stats)->toHaveKeys([
            'total_rfqs',
            'draft_rfqs',
            'sent_rfqs',
            'received_rfqs',
            'total_quantity',
        ]);
        expect($stats['total_rfqs'])->toBeGreaterThanOrEqual(5);
    }

    // ===== TESTES DE QUERIES =====

    /** @test */
    public function it_can_get_query_builder()
    {
        $query = $this->repository->getQuery();
        
        expect($query)->not->toBeNull();
        expect($query->count())->toBeGreaterThanOrEqual(0);
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
    public function it_can_count_rfqs_by_status()
    {
        RFQ::factory(3)->for($this->product)->create(['status' => 'draft']);
        
        $count = $this->repository->countByStatus('draft');
        
        expect($count)->toBeGreaterThanOrEqual(3);
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $this->expectException(\Exception::class);
        
        $this->repository->create([
            'rfq_number' => 'RFQ-123',
        ]);
    }

    /** @test */
    public function it_can_handle_multiple_rfqs_per_product()
    {
        RFQ::factory(5)->for($this->product)->create();
        
        $rfqs = $this->repository->getByProduct($this->product->id);
        
        expect($rfqs->count())->toBe(5);
    }

    /** @test */
    public function it_can_get_pending_rfqs()
    {
        RFQ::factory(2)->for($this->product)->create(['status' => 'sent']);
        RFQ::factory(1)->for($this->product)->create(['status' => 'received']);
        
        $pending = $this->repository->getPendingRFQs();
        
        expect($pending->count())->toBeGreaterThanOrEqual(2);
    }

    /** @test */
    public function it_can_get_total_quantity_by_status()
    {
        RFQ::factory(2)->for($this->product)->create([
            'status' => 'draft',
            'quantity' => 100,
        ]);
        
        $total = $this->repository->getTotalQuantityByStatus('draft');
        
        expect($total)->toBeGreaterThanOrEqual(200);
    }
}
