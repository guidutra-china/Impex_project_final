<?php

namespace Tests\Integration\Repositories;

use App\Models\Order;
use App\Models\Client;
use App\Models\User;
use App\Repositories\OrderRepository;
use Tests\TestCase;

class OrderRepositoryTest extends TestCase
{
    private OrderRepository $repository;
    private Client $client;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = app(OrderRepository::class);
        
        // Criar usuário e cliente para testes
        $this->user = User::factory()->create();
        $this->client = Client::factory()->for($this->user)->create();
    }

    /** @test */
    public function it_can_find_order_by_id()
    {
        $order = Order::factory()->for($this->client)->create();
        
        $found = $this->repository->findById($order->id);
        
        expect($found)->not->toBeNull();
        expect($found->id)->toBe($order->id);
    }

    /** @test */
    public function it_returns_null_when_order_not_found()
    {
        $found = $this->repository->findById(99999);
        
        expect($found)->toBeNull();
    }

    /** @test */
    public function it_can_get_all_orders()
    {
        Order::factory(3)->for($this->client)->create();
        
        $orders = $this->repository->all();
        
        expect($orders)->toHaveCount(3);
    }

    /** @test */
    public function it_can_create_order()
    {
        $data = [
            'customer_id' => $this->client->id,
            'currency_id' => 1,
            'status' => 'pending',
            'commission_percent' => 5.0,
            'commission_type' => 'embedded',
            'incoterm' => 'FOB',
            'incoterm_location' => 'Shanghai',
            'total_amount' => 10000,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ];
        
        $order = $this->repository->create($data);
        
        expect($order)->toBeInstanceOf(Order::class);
        expect($order->customer_id)->toBe($this->client->id);
        expect($order->status)->toBe('pending');
    }

    /** @test */
    public function it_can_update_order()
    {
        $order = Order::factory()->for($this->client)->create();
        
        $updated = $this->repository->update($order->id, [
            'status' => 'completed',
            'total_amount' => 20000,
        ]);
        
        expect($updated)->toBeTrue();
        expect($order->fresh()->status)->toBe('completed');
        expect($order->fresh()->total_amount)->toBe(20000);
    }

    /** @test */
    public function it_can_delete_order()
    {
        $order = Order::factory()->for($this->client)->create();
        
        $deleted = $this->repository->delete($order->id);
        
        expect($deleted)->toBeTrue();
        expect(Order::find($order->id))->toBeNull();
    }

    /** @test */
    public function it_can_get_pending_orders_by_customer()
    {
        Order::factory(2)->for($this->client)->create(['status' => 'pending']);
        Order::factory(1)->for($this->client)->create(['status' => 'completed']);
        
        $pending = $this->repository->getPendingOrdersByCustomer($this->client->id);
        
        expect($pending)->toHaveCount(2);
        expect($pending->every(fn($o) => $o->status === 'pending'))->toBeTrue();
    }

    /** @test */
    public function it_can_get_completed_orders()
    {
        Order::factory(2)->for($this->client)->create(['status' => 'completed']);
        Order::factory(1)->for($this->client)->create(['status' => 'pending']);
        
        $completed = $this->repository->getCompletedOrders();
        
        expect($completed->count())->toBeGreaterThanOrEqual(2);
        expect($completed->every(fn($o) => $o->status === 'completed'))->toBeTrue();
    }

    /** @test */
    public function it_can_get_orders_by_status()
    {
        Order::factory(3)->for($this->client)->create(['status' => 'pending']);
        
        $drafts = $this->repository->getOrdersByStatus('draft');
        
        expect($drafts->count())->toBeGreaterThanOrEqual(3);
        expect($drafts->every(fn($o) => $o->status === 'draft'))->toBeTrue();
    }

    /** @test */
    public function it_can_search_orders()
    {
        $order = Order::factory()->for($this->client)->create();
        
        $results = $this->repository->searchOrders($order->order_number);
        
        expect($results->pluck('id')->contains($order->id))->toBeTrue();
    }

    /** @test */
    public function it_can_count_orders_by_status()
    {
        Order::factory(3)->for($this->client)->create(['status' => 'pending']);
        
        $count = $this->repository->countByStatus('pending');
        
        expect($count)->toBeGreaterThanOrEqual(3);
    }

    /** @test */
    public function it_can_count_orders_by_customer()
    {
        Order::factory(4)->for($this->client)->create();
        
        $count = $this->repository->countByCustomer($this->client->id);
        
        expect($count)->toBe(4);
    }

    /** @test */
    public function it_can_get_total_amount_by_status()
    {
        Order::factory(2)->for($this->client)->create([
            'status' => 'completed',
            'total_amount' => 1000,
        ]);
        
        $total = $this->repository->getTotalAmountByStatus('completed');
        
        expect($total)->toBeGreaterThanOrEqual(2000);
    }

    /** @test */
    public function it_can_get_statistics()
    {
        Order::factory(5)->for($this->client)->create();
        
        $stats = $this->repository->getStatistics();
        
        expect($stats)->toHaveKeys(['total_orders', 'pending_orders', 'completed_orders', 'total_amount', 'average_amount']);
        expect($stats['total_orders'])->toBeGreaterThanOrEqual(5);
    }
}
