<?php

namespace Tests\Integration\Repositories;

use App\Models\Client;
use App\Models\User;
use App\Repositories\ClientRepository;
use Tests\TestCase;

class ClientRepositoryTest extends TestCase
{
    private ClientRepository $repository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = app(ClientRepository::class);
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_find_client_by_id()
    {
        $client = Client::factory()->for($this->user)->create();
        
        $found = $this->repository->findById($client->id);
        
        expect($found)->not->toBeNull();
        expect($found->id)->toBe($client->id);
    }

    /** @test */
    public function it_returns_null_when_client_not_found()
    {
        $found = $this->repository->findById(99999);
        
        expect($found)->toBeNull();
    }

    /** @test */
    public function it_can_get_all_clients()
    {
        Client::factory(3)->for($this->user)->create();
        
        $clients = $this->repository->all();
        
        expect($clients)->toHaveCount(3);
    }

    /** @test */
    public function it_can_create_client()
    {
        $data = [
            'user_id' => $this->user->id,
            'name' => 'Test Client',
            'code' => 'TEST',
            'email' => 'test@client.com',
            'country' => 'Brazil',
            'status' => 'active',
        ];
        
        $client = $this->repository->create($data);
        
        expect($client)->toBeInstanceOf(Client::class);
        expect($client->name)->toBe('Test Client');
        expect($client->code)->toBe('TEST');
    }

    /** @test */
    public function it_can_update_client()
    {
        $client = Client::factory()->for($this->user)->create();
        
        $updated = $this->repository->update($client->id, [
            'name' => 'Updated Client',
            'status' => 'inactive',
        ]);
        
        expect($updated)->toBeTrue();
        expect($client->fresh()->name)->toBe('Updated Client');
        expect($client->fresh()->status)->toBe('inactive');
    }

    /** @test */
    public function it_can_delete_client()
    {
        $client = Client::factory()->for($this->user)->create();
        
        $deleted = $this->repository->delete($client->id);
        
        expect($deleted)->toBeTrue();
        expect(Client::find($client->id))->toBeNull();
    }

    /** @test */
    public function it_can_get_active_clients()
    {
        Client::factory(2)->for($this->user)->create(['status' => 'active']);
        Client::factory(1)->for($this->user)->create(['status' => 'inactive']);
        
        $active = $this->repository->getActiveClients();
        
        expect($active->count())->toBeGreaterThanOrEqual(2);
        expect($active->every(fn($c) => $c->status === 'active'))->toBeTrue();
    }

    /** @test */
    public function it_can_get_clients_by_country()
    {
        Client::factory(3)->for($this->user)->create(['country' => 'Brazil']);
        
        $clients = $this->repository->getClientsByCountry('Brazil');
        
        expect($clients->count())->toBeGreaterThanOrEqual(3);
        expect($clients->every(fn($c) => $c->country === 'Brazil'))->toBeTrue();
    }

    /** @test */
    public function it_can_search_clients()
    {
        $client = Client::factory()->for($this->user)->create(['name' => 'Unique Client Name']);
        
        $results = $this->repository->searchClients('Unique Client');
        
        expect($results->pluck('id')->contains($client->id))->toBeTrue();
    }

    /** @test */
    public function it_can_get_clients_with_orders()
    {
        $client = Client::factory()->for($this->user)->create();
        $client->orders()->create([
            'currency_id' => 1,
            'status' => 'pending',
            'commission_percent' => 5.0,
            'commission_type' => 'embedded',
            'incoterm' => 'FOB',
            'incoterm_location' => 'Shanghai',
            'total_amount' => 10000,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);
        
        $clients = $this->repository->getClientsWithOrders();
        
        expect($clients->pluck('id')->contains($client->id))->toBeTrue();
    }

    /** @test */
    public function it_can_count_clients_by_country()
    {
        Client::factory(4)->for($this->user)->create(['country' => 'USA']);
        
        $count = $this->repository->countByCountry('USA');
        
        expect($count)->toBe(4);
    }

    /** @test */
    public function it_can_count_clients_by_status()
    {
        Client::factory(3)->for($this->user)->create(['status' => 'active']);
        
        $count = $this->repository->countByStatus('active');
        
        expect($count)->toBeGreaterThanOrEqual(3);
    }

    /** @test */
    public function it_can_get_statistics()
    {
        Client::factory(5)->for($this->user)->create();
        
        $stats = $this->repository->getStatistics();
        
        expect($stats)->toHaveKeys(['total_clients', 'active_clients', 'clients_with_orders', 'total_order_value']);
        expect($stats['total_clients'])->toBeGreaterThanOrEqual(5);
    }
}
