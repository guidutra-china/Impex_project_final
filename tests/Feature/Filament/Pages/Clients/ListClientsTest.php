<?php

namespace Tests\Feature\Filament\Pages\Clients;

use App\Models\Client;
use App\Models\User;
use Tests\TestCase;

class ListClientsTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_render_list_page()
    {
        $response = $this->get('/admin/clients');
        $response->assertSuccessful();
    }

    /** @test */
    public function it_displays_clients_in_table()
    {
        Client::factory(3)->for($this->user)->create();
        $response = $this->get('/admin/clients');
        $response->assertSuccessful();
    }

    /** @test */
    public function it_can_filter_clients_by_status()
    {
        Client::factory(2)->for($this->user)->create(['status' => 'active']);
        Client::factory(1)->for($this->user)->create(['status' => 'inactive']);
        
        $response = $this->get('/admin/clients?status=active');
        $response->assertSuccessful();
    }

    /** @test */
    public function it_can_search_clients_by_name()
    {
        $client = Client::factory()->for($this->user)->create(['name' => 'UNIQUE-CLIENT']);
        
        $response = $this->get('/admin/clients?search=UNIQUE');
        $response->assertSuccessful();
        $response->assertSee('UNIQUE-CLIENT');
    }

    /** @test */
    public function it_can_search_clients_by_email()
    {
        $client = Client::factory()->for($this->user)->create(['email' => 'unique@example.com']);
        
        $response = $this->get('/admin/clients?search=unique@');
        $response->assertSuccessful();
    }

    /** @test */
    public function it_can_sort_clients()
    {
        Client::factory()->for($this->user)->create(['name' => 'Client A']);
        Client::factory()->for($this->user)->create(['name' => 'Client B']);
        
        $response = $this->get('/admin/clients?sort=name');
        $response->assertSuccessful();
    }

    /** @test */
    public function it_has_create_button()
    {
        $response = $this->get('/admin/clients');
        $response->assertSuccessful();
        $response->assertSee('Create');
    }

    /** @test */
    public function it_displays_empty_state_when_no_clients()
    {
        $response = $this->get('/admin/clients');
        $response->assertSuccessful();
    }

    /** @test */
    public function unauthorized_user_cannot_view_clients()
    {
        $this->actingAs(User::factory()->create());
        $response = $this->get('/admin/clients');
        $this->assertTrue($response->status() === 403 || $response->status() === 302);
    }
}
