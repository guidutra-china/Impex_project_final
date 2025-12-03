<?php

namespace Tests\Integration\Repositories;

use App\Models\Event;
use App\Models\User;
use App\Repositories\EventRepository;
use Tests\TestCase;

class EventRepositoryTest extends TestCase
{
    private EventRepository $repository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = app(EventRepository::class);
        $this->user = User::factory()->create();
    }

    // ===== TESTES CRUD =====

    /** @test */
    public function it_can_find_event_by_id()
    {
        $event = Event::factory()->for($this->user)->create();
        
        $found = $this->repository->findById($event->id);
        
        expect($found)->not->toBeNull();
        expect($found->id)->toBe($event->id);
    }

    /** @test */
    public function it_returns_null_when_event_not_found()
    {
        $found = $this->repository->findById(99999);
        
        expect($found)->toBeNull();
    }

    /** @test */
    public function it_can_get_all_events()
    {
        Event::factory(3)->for($this->user)->create();
        
        $events = $this->repository->all();
        
        expect($events->count())->toBeGreaterThanOrEqual(3);
    }

    /** @test */
    public function it_can_create_event()
    {
        $data = [
            'title' => 'Test Event',
            'description' => 'Test Description',
            'status' => 'draft',
            'event_date' => now()->addDays(5),
            'created_by' => $this->user->id,
        ];
        
        $event = $this->repository->create($data);
        
        expect($event)->toBeInstanceOf(Event::class);
        expect($event->status)->toBe('pending');
    }

    /** @test */
    public function it_can_update_event()
    {
        $event = Event::factory()->for($this->user)->create();
        
        $updated = $this->repository->update($event->id, [
            'status' => 'completed',
        ]);
        
        expect($updated)->toBeTrue();
        expect($event->fresh()->status)->toBe('completed');
    }

    /** @test */
    public function it_can_delete_event()
    {
        $event = Event::factory()->for($this->user)->create();
        
        $deleted = $this->repository->delete($event->id);
        
        expect($deleted)->toBeTrue();
        expect(Event::find($event->id))->toBeNull();
    }

    // ===== TESTES DE FILTROS =====

    /** @test */
    public function it_can_get_events_by_status()
    {
        Event::factory(2)->for($this->user)->create(['status' => 'draft']);
        Event::factory(1)->for($this->user)->create(['status' => 'completed']);
        
        $pending = $this->repository->getByStatus('pending');
        
        expect($pending->count())->toBeGreaterThanOrEqual(2);
        expect($pending->every(fn($e) => $e->status === 'pending'))->toBeTrue();
    }

    /** @test */
    public function it_can_get_upcoming_events()
    {
        Event::factory(2)->for($this->user)->create(['event_date' => now()->addDays(5)]);
        Event::factory(1)->for($this->user)->create(['event_date' => now()->subDays(5)]);
        
        $upcoming = $this->repository->getUpcoming();
        
        expect($upcoming->count())->toBeGreaterThanOrEqual(2);
    }

    /** @test */
    public function it_can_get_completed_events()
    {
        Event::factory(2)->for($this->user)->create(['status' => 'completed']);
        Event::factory(1)->for($this->user)->create(['status' => 'draft']);
        
        $completed = $this->repository->getCompleted();
        
        expect($completed->count())->toBeGreaterThanOrEqual(2);
        expect($completed->every(fn($e) => $e->status === 'completed'))->toBeTrue();
    }

    // ===== TESTES DE BUSCA =====

    /** @test */
    public function it_can_search_events()
    {
        $event = Event::factory()
            ->for($this->user)
            ->create(['title' => 'UNIQUE-EVENT-12345']);
        
        $results = $this->repository->searchEvents('UNIQUE-EVENT');
        
        expect($results->pluck('id')->contains($event->id))->toBeTrue();
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
            'title' => 'Test',
        ]);
    }
}
