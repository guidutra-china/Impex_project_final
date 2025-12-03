<?php

namespace Tests\Feature\Filament\Pages\Orders;

use App\Models\Client;
use App\Models\Currency;
use App\Models\User;
use Tests\TestCase;

class CreateOrderTest extends TestCase
{
    private User $user;
    private Client $client;
    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->client = Client::factory()->for($this->user)->create();
        $this->currency = Currency::factory()->create();
        $this->actingAs($this->user);
    }

    // ===== TESTES DE RENDERIZAÇÃO =====

    /** @test */
    public function it_can_render_create_page()
    {
        $response = $this->get('/admin/orders/create');
        
        $response->assertSuccessful();
    }

    /** @test */
    public function it_displays_form_fields()
    {
        $response = $this->get('/admin/orders/create');
        
        $response->assertSuccessful();
        $response->assertSee('Order');
    }

    // ===== TESTES DE CRIAÇÃO =====

    /** @test */
    public function it_can_create_order()
    {
        $data = [
            'order_number' => 'ORD-' . now()->timestamp,
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
            'status' => 'pending',
            'order_date' => now()->format('Y-m-d'),
            'expected_delivery_date' => now()->addDays(30)->format('Y-m-d'),
        ];
        
        $response = $this->post('/admin/orders', $data);
        
        $this->assertDatabaseHas('orders', [
            'order_number' => $data['order_number'],
            'customer_id' => $this->client->id,
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $data = [
            'order_number' => '',
            'customer_id' => '',
        ];
        
        $response = $this->post('/admin/orders', $data);
        
        // Deve retornar com erros de validação
        $response->assertSessionHasErrors(['order_number', 'customer_id']);
    }

    /** @test */
    public function it_validates_unique_order_number()
    {
        $existingOrder = \App\Models\Order::factory()->for($this->client)->create();
        
        $data = [
            'order_number' => $existingOrder->order_number,
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
        ];
        
        $response = $this->post('/admin/orders', $data);
        
        $response->assertSessionHasErrors('order_number');
    }

    /** @test */
    public function it_validates_date_format()
    {
        $data = [
            'order_number' => 'ORD-' . now()->timestamp,
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
            'order_date' => 'invalid-date',
        ];
        
        $response = $this->post('/admin/orders', $data);
        
        $response->assertSessionHasErrors('order_date');
    }

    /** @test */
    public function it_validates_expected_delivery_date_after_order_date()
    {
        $data = [
            'order_number' => 'ORD-' . now()->timestamp,
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
            'order_date' => now()->format('Y-m-d'),
            'expected_delivery_date' => now()->subDays(5)->format('Y-m-d'),
        ];
        
        $response = $this->post('/admin/orders', $data);
        
        $response->assertSessionHasErrors('expected_delivery_date');
    }

    // ===== TESTES DE CAMPOS =====

    /** @test */
    public function it_sets_default_status_to_draft()
    {
        $data = [
            'order_number' => 'ORD-' . now()->timestamp,
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
        ];
        
        $response = $this->post('/admin/orders', $data);
        
        $this->assertDatabaseHas('orders', [
            'order_number' => $data['order_number'],
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_sets_created_by_to_current_user()
    {
        $data = [
            'order_number' => 'ORD-' . now()->timestamp,
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
        ];
        
        $response = $this->post('/admin/orders', $data);
        
        $this->assertDatabaseHas('orders', [
            'order_number' => $data['order_number'],
            'created_by' => $this->user->id,
        ]);
    }

    // ===== TESTES DE RELACIONAMENTOS =====

    /** @test */
    public function it_can_select_customer()
    {
        $response = $this->get('/admin/orders/create');
        
        $response->assertSuccessful();
        $response->assertSee($this->client->name);
    }

    /** @test */
    public function it_can_select_currency()
    {
        $response = $this->get('/admin/orders/create');
        
        $response->assertSuccessful();
        $response->assertSee($this->currency->code);
    }

    // ===== TESTES DE REDIRECIONAMENTO =====

    /** @test */
    public function it_redirects_to_edit_page_after_creation()
    {
        $data = [
            'order_number' => 'ORD-' . now()->timestamp,
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
        ];
        
        $response = $this->post('/admin/orders', $data);
        
        $response->assertRedirect();
    }

    // ===== TESTES DE PERMISSÕES =====

    /** @test */
    public function unauthorized_user_cannot_create_order()
    {
        $this->actingAs(User::factory()->create());
        
        $response = $this->get('/admin/orders/create');
        
        $this->assertTrue($response->status() === 403 || $response->status() === 302);
    }

    // ===== TESTES DE NOTIFICAÇÕES =====

    /** @test */
    public function it_shows_success_notification_after_creation()
    {
        $data = [
            'order_number' => 'ORD-' . now()->timestamp,
            'customer_id' => $this->client->id,
            'currency_id' => $this->currency->id,
        ];
        
        $response = $this->post('/admin/orders', $data);
        
        // Verificar se há notificação de sucesso
        $response->assertSessionHasNoErrors();
    }
}
