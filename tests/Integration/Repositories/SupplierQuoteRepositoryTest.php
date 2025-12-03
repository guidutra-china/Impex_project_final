<?php

namespace Tests\Integration\Repositories;

use App\Models\SupplierQuote;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\Client;
use App\Models\User;
use App\Repositories\SupplierQuoteRepository;
use Tests\TestCase;

class SupplierQuoteRepositoryTest extends TestCase
{
    private SupplierQuoteRepository $repository;
    private User $user;
    private Client $client;
    private Supplier $supplier;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = app(SupplierQuoteRepository::class);
        
        // Criar dados de teste
        $this->user = User::factory()->create();
        $this->client = Client::factory()->for($this->user)->create();
        $this->supplier = Supplier::factory()->for($this->user)->create();
        $this->order = Order::factory()->for($this->client)->create();
    }

    // ===== TESTES CRUD =====

    /** @test */
    public function it_can_find_supplier_quote_by_id()
    {
        $quote = SupplierQuote::factory()
            ->for($this->order)
            ->for($this->supplier)
            ->create();
        
        $found = $this->repository->findById($quote->id);
        
        expect($found)->not->toBeNull();
        expect($found->id)->toBe($quote->id);
    }

    /** @test */
    public function it_returns_null_when_supplier_quote_not_found()
    {
        $found = $this->repository->findById(99999);
        
        expect($found)->toBeNull();
    }

    /** @test */
    public function it_can_get_all_supplier_quotes()
    {
        SupplierQuote::factory(3)
            ->for($this->order)
            ->for($this->supplier)
            ->create();
        
        $quotes = $this->repository->all();
        
        expect($quotes->count())->toBeGreaterThanOrEqual(3);
    }

    /** @test */
    public function it_can_create_supplier_quote()
    {
        $data = [
            'order_id' => $this->order->id,
            'supplier_id' => $this->supplier->id,
            'quote_number' => 'SQ-' . now()->timestamp,
            'status' => 'draft',
            'total_price' => 100000,
            'currency_id' => 1,
            'exchange_rate' => 1.0,
            'exchange_rate_locked' => false,
            'created_by' => $this->user->id,
        ];
        
        $quote = $this->repository->create($data);
        
        expect($quote)->toBeInstanceOf(SupplierQuote::class);
        expect($quote->status)->toBe('pending');
        expect($quote->total_price)->toBe(100000);
    }

    /** @test */
    public function it_can_update_supplier_quote()
    {
        $quote = SupplierQuote::factory()
            ->for($this->order)
            ->for($this->supplier)
            ->create();
        
        $updated = $this->repository->update($quote->id, [
            'status' => 'sent',
            'total_price' => 150000,
        ]);
        
        expect($updated)->toBeTrue();
        expect($quote->fresh()->status)->toBe('approved');
        expect($quote->fresh()->total_price)->toBe(150000);
    }

    /** @test */
    public function it_can_delete_supplier_quote()
    {
        $quote = SupplierQuote::factory()
            ->for($this->order)
            ->for($this->supplier)
            ->create();
        
        $deleted = $this->repository->delete($quote->id);
        
        expect($deleted)->toBeTrue();
        expect(SupplierQuote::find($quote->id))->toBeNull();
    }

    // ===== TESTES DE FILTROS =====

    /** @test */
    public function it_can_get_supplier_quotes_by_status()
    {
        SupplierQuote::factory(2)
            ->for($this->order)
            ->for($this->supplier)
            ->create(['status' => 'draft']);
        SupplierQuote::factory(1)
            ->for($this->order)
            ->for($this->supplier)
            ->create(['status' => 'sent']);
        
        $pending = $this->repository->getByStatus('pending');
        
        expect($pending->count())->toBeGreaterThanOrEqual(2);
        expect($pending->every(fn($q) => $q->status === 'pending'))->toBeTrue();
    }

    /** @test */
    public function it_can_get_supplier_quotes_by_supplier()
    {
        SupplierQuote::factory(3)
            ->for($this->order)
            ->for($this->supplier)
            ->create();
        
        $quotes = $this->repository->getBySupplier($this->supplier->id);
        
        expect($quotes->count())->toBeGreaterThanOrEqual(3);
        expect($quotes->every(fn($q) => $q->supplier_id === $this->supplier->id))->toBeTrue();
    }

    /** @test */
    public function it_can_get_supplier_quotes_by_order()
    {
        SupplierQuote::factory(2)
            ->for($this->order)
            ->for($this->supplier)
            ->create();
        
        $quotes = $this->repository->getByOrder($this->order->id);
        
        expect($quotes->count())->toBe(2);
        expect($quotes->every(fn($q) => $q->order_id === $this->order->id))->toBeTrue();
    }

    // ===== TESTES DE CÁLCULOS E OPERAÇÕES =====

    /** @test */
    public function it_can_recalculate_quote()
    {
        $quote = SupplierQuote::factory()
            ->for($this->order)
            ->for($this->supplier)
            ->create(['total_price' => 100000]);
        
        $result = $this->repository->recalculate($quote->id);
        
        expect($result)->toBeTrue();
    }

    /** @test */
    public function it_can_lock_exchange_rate()
    {
        $quote = SupplierQuote::factory()
            ->for($this->order)
            ->for($this->supplier)
            ->create(['exchange_rate_locked' => false]);
        
        $result = $this->repository->lockExchangeRate($quote->id);
        
        expect($result)->toBeTrue();
        expect($quote->fresh()->exchange_rate_locked)->toBeTrue();
    }

    /** @test */
    public function it_can_unlock_exchange_rate()
    {
        $quote = SupplierQuote::factory()
            ->for($this->order)
            ->for($this->supplier)
            ->create(['exchange_rate_locked' => true]);
        
        $result = $this->repository->unlockExchangeRate($quote->id);
        
        expect($result)->toBeTrue();
        expect($quote->fresh()->exchange_rate_locked)->toBeFalse();
    }

    // ===== TESTES DE TRANSIÇÕES DE ESTADO =====

    /** @test */
    public function it_can_approve_supplier_quote()
    {
        $quote = SupplierQuote::factory()
            ->for($this->order)
            ->for($this->supplier)
            ->create(['status' => 'draft']);
        
        $result = $this->repository->approve($quote->id);
        
        expect($result)->toBeTrue();
        expect($quote->fresh()->status)->toBe('approved');
    }

    /** @test */
    public function it_can_reject_supplier_quote()
    {
        $quote = SupplierQuote::factory()
            ->for($this->order)
            ->for($this->supplier)
            ->create(['status' => 'draft']);
        
        $result = $this->repository->reject($quote->id);
        
        expect($result)->toBeTrue();
        expect($quote->fresh()->status)->toBe('rejected');
    }

    // ===== TESTES DE COMPARAÇÃO =====

    /** @test */
    public function it_can_find_cheapest_quote()
    {
        $cheap = SupplierQuote::factory()
            ->for($this->order)
            ->for($this->supplier)
            ->create(['total_price' => 50000]);
        
        SupplierQuote::factory()
            ->for($this->order)
            ->for($this->supplier)
            ->create(['total_price' => 100000]);
        
        $cheapest = $this->repository->getCheapest($this->order->id);
        
        expect($cheapest)->not->toBeNull();
        expect($cheapest->id)->toBe($cheap->id);
    }

    /** @test */
    public function it_can_find_most_expensive_quote()
    {
        SupplierQuote::factory()
            ->for($this->order)
            ->for($this->supplier)
            ->create(['total_price' => 50000]);
        
        $expensive = SupplierQuote::factory()
            ->for($this->order)
            ->for($this->supplier)
            ->create(['total_price' => 100000]);
        
        $mostExpensive = $this->repository->getMostExpensive($this->order->id);
        
        expect($mostExpensive)->not->toBeNull();
        expect($mostExpensive->id)->toBe($expensive->id);
    }

    /** @test */
    public function it_can_compare_quotes()
    {
        $quote1 = SupplierQuote::factory()
            ->for($this->order)
            ->for($this->supplier)
            ->create(['total_price' => 50000]);
        
        $quote2 = SupplierQuote::factory()
            ->for($this->order)
            ->for($this->supplier)
            ->create(['total_price' => 100000]);
        
        $comparison = $this->repository->compareQuotes([$quote1->id, $quote2->id]);
        
        expect($comparison)->toBeArray();
        expect(count($comparison))->toBe(2);
    }

    // ===== TESTES DE BUSCA =====

    /** @test */
    public function it_can_search_supplier_quotes()
    {
        $quote = SupplierQuote::factory()
            ->for($this->order)
            ->for($this->supplier)
            ->create(['quote_number' => 'SQ-UNIQUE-12345']);
        
        $results = $this->repository->searchQuotes('SQ-UNIQUE');
        
        expect($results->pluck('id')->contains($quote->id))->toBeTrue();
    }

    // ===== TESTES DE ESTATÍSTICAS =====

    /** @test */
    public function it_can_get_statistics()
    {
        SupplierQuote::factory(5)
            ->for($this->order)
            ->for($this->supplier)
            ->create();
        
        $stats = $this->repository->getStatistics();
        
        expect($stats)->toHaveKeys([
            'total_quotes',
            'pending_quotes',
            'approved_quotes',
            'average_price',
        ]);
        expect($stats['total_quotes'])->toBeGreaterThanOrEqual(5);
    }

    // ===== TESTES DE QUERIES =====

    /** @test */
    public function it_can_get_query_builder()
    {
        $query = $this->repository->getQuery();
        
        expect($query)->not->toBeNull();
        expect($query->count())->toBeGreaterThanOrEqual(0);
    }

    /** @test */
    public function it_can_get_items_query()
    {
        $quote = SupplierQuote::factory()
            ->for($this->order)
            ->for($this->supplier)
            ->create();
        
        $query = $this->repository->getItemsQuery($quote->id);
        
        expect($query)->not->toBeNull();
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
    public function it_handles_multiple_quotes_per_order()
    {
        SupplierQuote::factory(5)
            ->for($this->order)
            ->for($this->supplier)
            ->create();
        
        $quotes = $this->repository->getByOrder($this->order->id);
        
        expect($quotes->count())->toBe(5);
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $this->expectException(\Exception::class);
        
        $this->repository->create([
            'quote_number' => 'SQ-123',
            // Missing required fields
        ]);
    }

    /** @test */
    public function it_can_count_quotes_by_status()
    {
        SupplierQuote::factory(3)
            ->for($this->order)
            ->for($this->supplier)
            ->create(['status' => 'draft']);
        
        $count = $this->repository->countByStatus('pending');
        
        expect($count)->toBeGreaterThanOrEqual(3);
    }

    /** @test */
    public function it_can_get_average_price()
    {
        SupplierQuote::factory()
            ->for($this->order)
            ->for($this->supplier)
            ->create(['total_price' => 100000]);
        
        SupplierQuote::factory()
            ->for($this->order)
            ->for($this->supplier)
            ->create(['total_price' => 200000]);
        
        $average = $this->repository->getAveragePrice($this->order->id);
        
        expect($average)->toBe(150000);
    }
}
