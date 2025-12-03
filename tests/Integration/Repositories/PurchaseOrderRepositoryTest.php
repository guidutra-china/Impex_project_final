<?php

namespace Tests\Integration\Repositories;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Repositories\PurchaseOrderRepository;
use Tests\TestCase;

class PurchaseOrderRepositoryTest extends TestCase
{
    private PurchaseOrderRepository $repository;
    private User $user;
    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = app(PurchaseOrderRepository::class);
        
        // Criar dados de teste
        $this->user = User::factory()->create();
        $this->supplier = Supplier::factory()->for($this->user)->create();
    }

    // ===== TESTES CRUD =====

    /** @test */
    public function it_can_find_purchase_order_by_id()
    {
        $po = PurchaseOrder::factory()->for($this->supplier)->create();
        
        $found = $this->repository->findById($po->id);
        
        expect($found)->not->toBeNull();
        expect($found->id)->toBe($po->id);
    }

    /** @test */
    public function it_returns_null_when_purchase_order_not_found()
    {
        $found = $this->repository->findById(99999);
        
        expect($found)->toBeNull();
    }

    /** @test */
    public function it_can_get_all_purchase_orders()
    {
        PurchaseOrder::factory(3)->for($this->supplier)->create();
        
        $orders = $this->repository->all();
        
        expect($orders->count())->toBeGreaterThanOrEqual(3);
    }

    /** @test */
    public function it_can_create_purchase_order()
    {
        $data = [
            'supplier_id' => $this->supplier->id,
            'po_number' => 'PO-' . now()->timestamp,
            'status' => 'pending',
            'total_amount' => 100000,
            'currency_id' => 1,
            'po_date' => now(),
            'expected_delivery_date' => now()->addDays(30),
            'created_by' => $this->user->id,
        ];
        
        $po = $this->repository->create($data);
        
        expect($po)->toBeInstanceOf(PurchaseOrder::class);
        expect($po->status)->toBe('draft');
        expect($po->total_amount)->toBe(100000);
    }

    /** @test */
    public function it_can_update_purchase_order()
    {
        $po = PurchaseOrder::factory()->for($this->supplier)->create();
        
        $updated = $this->repository->update($po->id, [
            'status' => 'processing',
            'total_amount' => 150000,
        ]);
        
        expect($updated)->toBeTrue();
        expect($po->fresh()->status)->toBe('confirmed');
        expect($po->fresh()->total_amount)->toBe(150000);
    }

    /** @test */
    public function it_can_delete_purchase_order()
    {
        $po = PurchaseOrder::factory()->for($this->supplier)->create();
        
        $deleted = $this->repository->delete($po->id);
        
        expect($deleted)->toBeTrue();
        expect(PurchaseOrder::find($po->id))->toBeNull();
    }

    // ===== TESTES DE FILTROS =====

    /** @test */
    public function it_can_get_purchase_orders_by_status()
    {
        PurchaseOrder::factory(2)->for($this->supplier)->create(['status' => 'pending']);
        PurchaseOrder::factory(1)->for($this->supplier)->create(['status' => 'processing']);
        
        $drafts = $this->repository->getByStatus('draft');
        
        expect($drafts->count())->toBeGreaterThanOrEqual(2);
        expect($drafts->every(fn($po) => $po->status === 'draft'))->toBeTrue();
    }

    /** @test */
    public function it_can_get_purchase_orders_by_supplier()
    {
        PurchaseOrder::factory(3)->for($this->supplier)->create();
        
        $orders = $this->repository->getBySupplier($this->supplier->id);
        
        expect($orders->count())->toBeGreaterThanOrEqual(3);
        expect($orders->every(fn($po) => $po->supplier_id === $this->supplier->id))->toBeTrue();
    }

    // ===== TESTES DE CÁLCULOS ESPECÍFICOS =====

    /** @test */
    public function it_can_get_total_active_amount()
    {
        PurchaseOrder::factory(2)->for($this->supplier)->create([
            'status' => 'processing',
            'total_amount' => 100000,
        ]);
        
        $total = $this->repository->getTotalActive();
        
        expect($total)->toBeGreaterThanOrEqual(200000);
    }

    /** @test */
    public function it_can_get_total_pending_amount()
    {
        PurchaseOrder::factory(2)->for($this->supplier)->create([
            'status' => 'pending',
            'total_amount' => 100000,
        ]);
        
        $total = $this->repository->getTotalPending();
        
        expect($total)->toBeGreaterThanOrEqual(200000);
    }

    /** @test */
    public function it_can_count_active_purchase_orders()
    {
        PurchaseOrder::factory(3)->for($this->supplier)->create(['status' => 'processing']);
        
        $count = $this->repository->countActive();
        
        expect($count)->toBeGreaterThanOrEqual(3);
    }

    // ===== TESTES DE BUSCA =====

    /** @test */
    public function it_can_search_purchase_orders()
    {
        $po = PurchaseOrder::factory()
            ->for($this->supplier)
            ->create(['po_number' => 'PO-UNIQUE-12345']);
        
        $results = $this->repository->searchOrders('PO-UNIQUE');
        
        expect($results->pluck('id')->contains($po->id))->toBeTrue();
    }

    // ===== TESTES DE ESTATÍSTICAS =====

    /** @test */
    public function it_can_get_statistics()
    {
        PurchaseOrder::factory(5)->for($this->supplier)->create();
        
        $stats = $this->repository->getStatistics();
        
        expect($stats)->toHaveKeys([
            'total_orders',
            'draft_orders',
            'confirmed_orders',
            'received_orders',
            'total_amount',
            'average_amount',
        ]);
        expect($stats['total_orders'])->toBeGreaterThanOrEqual(5);
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
    public function it_handles_large_amounts_correctly()
    {
        $po = PurchaseOrder::factory()
            ->for($this->supplier)
            ->create(['total_amount' => 999999999999]);
        
        $found = $this->repository->findById($po->id);
        
        expect($found->total_amount)->toBe(999999999999);
    }

    /** @test */
    public function it_can_count_orders_by_status()
    {
        PurchaseOrder::factory(3)->for($this->supplier)->create(['status' => 'pending']);
        
        $count = $this->repository->countByStatus('draft');
        
        expect($count)->toBeGreaterThanOrEqual(3);
    }

    /** @test */
    public function it_can_get_total_amount_by_status()
    {
        PurchaseOrder::factory(2)->for($this->supplier)->create([
            'status' => 'processing',
            'total_amount' => 100000,
        ]);
        
        $total = $this->repository->getTotalByStatus('confirmed');
        
        expect($total)->toBeGreaterThanOrEqual(200000);
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $this->expectException(\Exception::class);
        
        $this->repository->create([
            'po_number' => 'PO-123',
            // Missing required fields
        ]);
    }

    /** @test */
    public function it_can_handle_multiple_orders_per_supplier()
    {
        PurchaseOrder::factory(3)->for($this->supplier)->create();
        
        $orders = $this->repository->getBySupplier($this->supplier->id);
        
        expect($orders->count())->toBe(3);
    }

    /** @test */
    public function it_can_get_orders_by_date_range()
    {
        $startDate = now()->subDays(10);
        $endDate = now()->addDays(10);
        
        PurchaseOrder::factory(2)->for($this->supplier)->create([
            'po_date' => now(),
        ]);
        
        $orders = $this->repository->getByDateRange($startDate, $endDate);
        
        expect($orders->count())->toBeGreaterThanOrEqual(2);
    }

    /** @test */
    public function it_can_get_pending_delivery_orders()
    {
        PurchaseOrder::factory(2)->for($this->supplier)->create([
            'status' => 'processing',
            'expected_delivery_date' => now()->addDays(10),
        ]);
        
        PurchaseOrder::factory(1)->for($this->supplier)->create([
            'status' => 'received',
        ]);
        
        $pending = $this->repository->getPendingDelivery();
        
        expect($pending->count())->toBeGreaterThanOrEqual(2);
    }

    /** @test */
    public function it_can_get_overdue_delivery_orders()
    {
        PurchaseOrder::factory(2)->for($this->supplier)->create([
            'status' => 'processing',
            'expected_delivery_date' => now()->subDays(5),
        ]);
        
        $overdue = $this->repository->getOverdueDelivery();
        
        expect($overdue->count())->toBeGreaterThanOrEqual(2);
    }

    /** @test */
    public function it_can_approve_purchase_order()
    {
        $po = PurchaseOrder::factory()
            ->for($this->supplier)
            ->create(['status' => 'pending']);
        
        $result = $this->repository->approve($po->id);
        
        expect($result)->toBeTrue();
        expect($po->fresh()->status)->toBe('approved');
    }

    /** @test */
    public function it_can_reject_purchase_order()
    {
        $po = PurchaseOrder::factory()
            ->for($this->supplier)
            ->create(['status' => 'pending']);
        
        $result = $this->repository->reject($po->id);
        
        expect($result)->toBeTrue();
        expect($po->fresh()->status)->toBe('rejected');
    }
}
