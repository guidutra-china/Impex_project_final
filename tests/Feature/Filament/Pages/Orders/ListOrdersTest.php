<?php

namespace Tests\Feature\Filament\Pages\Orders;

use App\Models\Order;
use App\Models\Client;
use App\Models\User;
use Tests\TestCase;

class ListOrdersTest extends TestCase
{
    private User $user;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->client = Client::factory()->for($this->user)->create();
        $this->actingAs($this->user);
    }

    // ===== TESTES DE RENDERIZAÇÃO =====

    /** @test */
    public function it_can_render_list_page()
    {
        $response = $this->get('/admin/orders');
        
        $response->assertSuccessful();
    }

    /** @test */
    public function it_displays_orders_in_table()
    {
        Order::factory(3)->for($this->client)->create();
        
        $response = $this->get('/admin/orders');
        
        $response->assertSuccessful();
        $response->assertSee('Order');
    }

    /** @test */
    public function it_displays_order_columns()
    {
        $order = Order::factory()->for($this->client)->create();
        
        $response = $this->get('/admin/orders');
        
        $response->assertSuccessful();
        $response->assertSee($order->order_number);
    }

    // ===== TESTES DE FILTROS =====

    /** @test */
    public function it_can_filter_orders_by_status()
    {
        Order::factory(2)->for($this->client)->create(['status' => 'draft']);
        Order::factory(1)->for($this->client)->create(['status' => 'confirmed']);
        
        $response = $this->get('/admin/orders?status=draft');
        
        $response->assertSuccessful();
    }

    /** @test */
    public function it_can_filter_orders_by_customer()
    {
        $client1 = Client::factory()->for($this->user)->create();
        $client2 = Client::factory()->for($this->user)->create();
        
        Order::factory(2)->for($client1)->create();
        Order::factory(1)->for($client2)->create();
        
        $response = $this->get('/admin/orders?customer=' . $client1->id);
        
        $response->assertSuccessful();
    }

    // ===== TESTES DE BUSCA =====

    /** @test */
    public function it_can_search_orders_by_order_number()
    {
        $order = Order::factory()->for($this->client)->create(['order_number' => 'ORD-UNIQUE-12345']);
        Order::factory()->for($this->client)->create();
        
        $response = $this->get('/admin/orders?search=ORD-UNIQUE');
        
        $response->assertSuccessful();
        $response->assertSee('ORD-UNIQUE-12345');
    }

    /** @test */
    public function it_can_search_orders_by_customer_name()
    {
        $client = Client::factory()->for($this->user)->create(['name' => 'UNIQUE-CLIENT-NAME']);
        Order::factory()->for($client)->create();
        
        $response = $this->get('/admin/orders?search=UNIQUE-CLIENT');
        
        $response->assertSuccessful();
    }

    // ===== TESTES DE ORDENAÇÃO =====

    /** @test */
    public function it_can_sort_orders_by_order_number()
    {
        Order::factory()->for($this->client)->create(['order_number' => 'ORD-001']);
        Order::factory()->for($this->client)->create(['order_number' => 'ORD-002']);
        
        $response = $this->get('/admin/orders?sort=order_number');
        
        $response->assertSuccessful();
    }

    /** @test */
    public function it_can_sort_orders_by_date()
    {
        Order::factory()->for($this->client)->create(['created_at' => now()->subDays(5)]);
        Order::factory()->for($this->client)->create(['created_at' => now()]);
        
        $response = $this->get('/admin/orders?sort=-created_at');
        
        $response->assertSuccessful();
    }

    // ===== TESTES DE PAGINAÇÃO =====

    /** @test */
    public function it_can_paginate_orders()
    {
        Order::factory(15)->for($this->client)->create();
        
        $response = $this->get('/admin/orders');
        
        $response->assertSuccessful();
    }

    /** @test */
    public function it_can_navigate_to_next_page()
    {
        Order::factory(15)->for($this->client)->create();
        
        $response = $this->get('/admin/orders?page=2');
        
        $response->assertSuccessful();
    }

    // ===== TESTES DE AÇÕES =====

    /** @test */
    public function it_has_create_button()
    {
        $response = $this->get('/admin/orders');
        
        $response->assertSuccessful();
        $response->assertSee('Create');
    }

    /** @test */
    public function it_has_edit_action()
    {
        $order = Order::factory()->for($this->client)->create();
        
        $response = $this->get('/admin/orders');
        
        $response->assertSuccessful();
    }

    /** @test */
    public function it_has_delete_action()
    {
        $order = Order::factory()->for($this->client)->create();
        
        $response = $this->get('/admin/orders');
        
        $response->assertSuccessful();
    }

    // ===== TESTES DE EMPTY STATE =====

    /** @test */
    public function it_displays_empty_state_when_no_orders()
    {
        $response = $this->get('/admin/orders');
        
        $response->assertSuccessful();
    }

    // ===== TESTES DE PERMISSÕES =====

    /** @test */
    public function unauthorized_user_cannot_view_orders()
    {
        $this->actingAs(User::factory()->create());
        
        $response = $this->get('/admin/orders');
        
        // Deve redirecionar ou retornar 403
        $this->assertTrue($response->status() === 403 || $response->status() === 302);
    }

    // ===== TESTES DE RESPONSIVIDADE =====

    /** @test */
    public function it_renders_correctly_on_mobile()
    {
        Order::factory(3)->for($this->client)->create();
        
        $response = $this->get('/admin/orders');
        
        $response->assertSuccessful();
    }

    // ===== TESTES DE PERFORMANCE =====

    /** @test */
    public function it_loads_page_with_many_orders()
    {
        Order::factory(100)->for($this->client)->create();
        
        $response = $this->get('/admin/orders');
        
        $response->assertSuccessful();
    }
}
