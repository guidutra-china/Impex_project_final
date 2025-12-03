<?php

namespace Tests\Integration\Repositories;

use App\Models\Shipment;
use App\Models\Order;
use App\Models\Client;
use App\Models\User;
use App\Repositories\ShipmentRepository;
use Tests\TestCase;

class ShipmentRepositoryTest extends TestCase
{
    private ShipmentRepository $repository;
    private User $user;
    private Client $client;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = app(ShipmentRepository::class);
        
        $this->user = User::factory()->create();
        $this->client = Client::factory()->for($this->user)->create();
        $this->order = Order::factory()->for($this->client)->create();
    }

    // ===== TESTES CRUD =====

    /** @test */
    public function it_can_find_shipment_by_id()
    {
        $shipment = Shipment::factory()->for($this->order)->create();
        
        $found = $this->repository->findById($shipment->id);
        
        expect($found)->not->toBeNull();
        expect($found->id)->toBe($shipment->id);
    }

    /** @test */
    public function it_returns_null_when_shipment_not_found()
    {
        $found = $this->repository->findById(99999);
        
        expect($found)->toBeNull();
    }

    /** @test */
    public function it_can_get_all_shipments()
    {
        Shipment::factory(3)->for($this->order)->create();
        
        $shipments = $this->repository->all();
        
        expect($shipments->count())->toBeGreaterThanOrEqual(3);
    }

    /** @test */
    public function it_can_create_shipment()
    {
        $data = [
            'order_id' => $this->order->id,
            'shipment_number' => 'SHP-' . now()->timestamp,
            'status' => 'pending',
            'shipment_date' => now(),
            'expected_delivery_date' => now()->addDays(30),
            'created_by' => $this->user->id,
        ];
        
        $shipment = $this->repository->create($data);
        
        expect($shipment)->toBeInstanceOf(Shipment::class);
        expect($shipment->status)->toBe('pending');
    }

    /** @test */
    public function it_can_update_shipment()
    {
        $shipment = Shipment::factory()->for($this->order)->create();
        
        $updated = $this->repository->update($shipment->id, [
            'status' => 'shipped',
        ]);
        
        expect($updated)->toBeTrue();
        expect($shipment->fresh()->status)->toBe('shipped');
    }

    /** @test */
    public function it_can_delete_shipment()
    {
        $shipment = Shipment::factory()->for($this->order)->create();
        
        $deleted = $this->repository->delete($shipment->id);
        
        expect($deleted)->toBeTrue();
        expect(Shipment::find($shipment->id))->toBeNull();
    }

    // ===== TESTES DE FILTROS =====

    /** @test */
    public function it_can_get_shipments_by_status()
    {
        Shipment::factory(2)->for($this->order)->create(['status' => 'pending']);
        Shipment::factory(1)->for($this->order)->create(['status' => 'shipped']);
        
        $pending = $this->repository->getByStatus('pending');
        
        expect($pending->count())->toBeGreaterThanOrEqual(2);
        expect($pending->every(fn($s) => $s->status === 'pending'))->toBeTrue();
    }

    // ===== TESTES DE QUERIES =====

    /** @test */
    public function it_can_get_items_query()
    {
        $shipment = Shipment::factory()->for($this->order)->create();
        
        $query = $this->repository->getItemsQuery($shipment->id);
        
        expect($query)->not->toBeNull();
    }

    /** @test */
    public function it_can_get_invoices_query()
    {
        $shipment = Shipment::factory()->for($this->order)->create();
        
        $query = $this->repository->getInvoicesQuery($shipment->id);
        
        expect($query)->not->toBeNull();
    }

    /** @test */
    public function it_can_get_packing_boxes_query()
    {
        $shipment = Shipment::factory()->for($this->order)->create();
        
        $query = $this->repository->getPackingBoxesQuery($shipment->id);
        
        expect($query)->not->toBeNull();
    }

    // ===== TESTES DE BUSCA =====

    /** @test */
    public function it_can_search_shipments()
    {
        $shipment = Shipment::factory()
            ->for($this->order)
            ->create(['shipment_number' => 'SHP-UNIQUE-12345']);
        
        $results = $this->repository->searchShipments('SHP-UNIQUE');
        
        expect($results->pluck('id')->contains($shipment->id))->toBeTrue();
    }

    // ===== TESTES DE ESTATÍSTICAS =====

    /** @test */
    public function it_can_get_statistics()
    {
        Shipment::factory(5)->for($this->order)->create();
        
        $stats = $this->repository->getStatistics();
        
        expect($stats)->toHaveKeys([
            'total_shipments',
            'pending_shipments',
            'shipped_shipments',
            'delivered_shipments',
        ]);
        expect($stats['total_shipments'])->toBeGreaterThanOrEqual(5);
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
    public function it_can_count_shipments_by_status()
    {
        Shipment::factory(3)->for($this->order)->create(['status' => 'pending']);
        
        $count = $this->repository->countByStatus('pending');
        
        expect($count)->toBeGreaterThanOrEqual(3);
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $this->expectException(\Exception::class);
        
        $this->repository->create([
            'shipment_number' => 'SHP-123',
        ]);
    }
}
