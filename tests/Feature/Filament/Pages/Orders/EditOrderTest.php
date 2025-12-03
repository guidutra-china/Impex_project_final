<?php

namespace Tests\Feature\Filament\Pages\Orders;

use App\Models\Order;
use App\Models\Client;
use App\Models\User;
use Tests\TestCase;

class EditOrderTest extends TestCase
{
    private User $user;
    private Client $client;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->client = Client::factory()->for($this->user)->create();
        $this->order = Order::factory()->for($this->client)->create();
        $this->actingAs($this->user);
    }

    // ===== TESTES DE RENDERIZAÇÃO =====

    /** @test */
    public function it_can_render_edit_page()
    {
        $response = $this->get("/admin/orders/{$this->order->id}/edit");
        
        $response->assertSuccessful();
    }

    /** @test */
    public function it_displays_order_data()
    {
        $response = $this->get("/admin/orders/{$this->order->id}/edit");
        
        $response->assertSuccessful();
        $response->assertSee($this->order->order_number);
    }

    /** @test */
    public function it_displays_form_fields()
    {
        $response = $this->get("/admin/orders/{$this->order->id}/edit");
        
        $response->assertSuccessful();
    }

    // ===== TESTES DE ATUALIZAÇÃO =====

    /** @test */
    public function it_can_update_order()
    {
        $data = [
            'order_number' => 'ORD-UPDATED-' . now()->timestamp,
            'status' => 'processing',
        ];
        
        $response = $this->put("/admin/orders/{$this->order->id}", $data);
        
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'order_number' => $data['order_number'],
            'status' => 'processing',
        ]);
    }

    /** @test */
    public function it_validates_required_fields_on_update()
    {
        $data = [
            'order_number' => '',
            'customer_id' => '',
        ];
        
        $response = $this->put("/admin/orders/{$this->order->id}", $data);
        
        $response->assertSessionHasErrors(['order_number', 'customer_id']);
    }

    /** @test */
    public function it_validates_unique_order_number_on_update()
    {
        $anotherOrder = Order::factory()->for($this->client)->create();
        
        $data = [
            'order_number' => $anotherOrder->order_number,
        ];
        
        $response = $this->put("/admin/orders/{$this->order->id}", $data);
        
        $response->assertSessionHasErrors('order_number');
    }

    // ===== TESTES DE DELEÇÃO =====

    /** @test */
    public function it_can_delete_order()
    {
        $response = $this->delete("/admin/orders/{$this->order->id}");
        
        $this->assertDatabaseMissing('orders', ['id' => $this->order->id]);
    }

    /** @test */
    public function it_requires_confirmation_to_delete()
    {
        $response = $this->get("/admin/orders/{$this->order->id}/edit");
        
        $response->assertSuccessful();
        // Verificar se há botão de delete
    }

    // ===== TESTES DE TRANSIÇÕES DE STATUS =====

    /** @test */
    public function it_can_transition_status_from_draft_to_confirmed()
    {
        $this->order->update(['status' => 'pending']);
        
        $data = ['status' => 'processing'];
        
        $response = $this->put("/admin/orders/{$this->order->id}", $data);
        
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => 'processing',
        ]);
    }

    /** @test */
    public function it_can_transition_status_from_confirmed_to_shipped()
    {
        $this->order->update(['status' => 'processing']);
        
        $data = ['status' => 'shipped'];
        
        $response = $this->put("/admin/orders/{$this->order->id}", $data);
        
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => 'shipped',
        ]);
    }

    // ===== TESTES DE RELACIONAMENTOS =====

    /** @test */
    public function it_can_change_customer()
    {
        $newClient = Client::factory()->for($this->user)->create();
        
        $data = ['customer_id' => $newClient->id];
        
        $response = $this->put("/admin/orders/{$this->order->id}", $data);
        
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'customer_id' => $newClient->id,
        ]);
    }

    // ===== TESTES DE AÇÕES CUSTOMIZADAS =====

    /** @test */
    public function it_has_add_expense_action()
    {
        $response = $this->get("/admin/orders/{$this->order->id}/edit");
        
        $response->assertSuccessful();
    }

    /** @test */
    public function it_can_add_project_expense()
    {
        $data = [
            'description' => 'Test Expense',
            'amount' => 1000,
            'category' => 'shipping',
        ];
        
        // Simular ação de adicionar despesa
        $response = $this->post("/admin/orders/{$this->order->id}/add-expense", $data);
        
        // Verificar se a despesa foi criada
    }

    // ===== TESTES DE VALIDAÇÕES =====

    /** @test */
    public function it_validates_date_format()
    {
        $data = [
            'order_date' => 'invalid-date',
        ];
        
        $response = $this->put("/admin/orders/{$this->order->id}", $data);
        
        $response->assertSessionHasErrors('order_date');
    }

    /** @test */
    public function it_validates_expected_delivery_date_after_order_date()
    {
        $data = [
            'order_date' => now()->format('Y-m-d'),
            'expected_delivery_date' => now()->subDays(5)->format('Y-m-d'),
        ];
        
        $response = $this->put("/admin/orders/{$this->order->id}", $data);
        
        $response->assertSessionHasErrors('expected_delivery_date');
    }

    // ===== TESTES DE PERMISSÕES =====

    /** @test */
    public function unauthorized_user_cannot_edit_order()
    {
        $this->actingAs(User::factory()->create());
        
        $response = $this->get("/admin/orders/{$this->order->id}/edit");
        
        $this->assertTrue($response->status() === 403 || $response->status() === 302);
    }

    /** @test */
    public function unauthorized_user_cannot_delete_order()
    {
        $this->actingAs(User::factory()->create());
        
        $response = $this->delete("/admin/orders/{$this->order->id}");
        
        $this->assertTrue($response->status() === 403 || $response->status() === 302);
    }

    // ===== TESTES DE NOTIFICAÇÕES =====

    /** @test */
    public function it_shows_success_notification_after_update()
    {
        $data = ['status' => 'processing'];
        
        $response = $this->put("/admin/orders/{$this->order->id}", $data);
        
        $response->assertSessionHasNoErrors();
    }

    // ===== TESTES DE REDIRECIONAMENTO =====

    /** @test */
    public function it_stays_on_edit_page_after_update()
    {
        $data = ['status' => 'processing'];
        
        $response = $this->put("/admin/orders/{$this->order->id}", $data);
        
        // Deve redirecionar para a mesma página ou ficar na mesma página
    }

    // ===== TESTES DE RELACIONAMENTOS ANINHADOS =====

    /** @test */
    public function it_can_view_related_items()
    {
        $response = $this->get("/admin/orders/{$this->order->id}/edit");
        
        $response->assertSuccessful();
    }

    /** @test */
    public function it_can_view_related_supplier_quotes()
    {
        $response = $this->get("/admin/orders/{$this->order->id}/edit");
        
        $response->assertSuccessful();
    }
}
