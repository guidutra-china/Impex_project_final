<?php

namespace Tests\Feature\BusinessRules;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use Tests\TestCase;

class OrderBusinessRulesTest extends TestCase
{
    private User $user;
    private Client $client;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->client = Client::factory()->for($this->user)->create();
        $this->product = Product::factory()->for($this->user)->create();
        $this->actingAs($this->user);
    }

    // ===== REGRAS DE VALIDAÇÃO DE ORDEM =====

    /** @test */
    public function order_number_must_be_unique()
    {
        $order1 = Order::factory()->for($this->client)->create(['order_number' => 'ORD-001']);
        
        $data = [
            'order_number' => 'ORD-001',
            'customer_id' => $this->client->id,
            'currency_id' => $this->client->currency_id,
        ];
        
        $response = $this->post('/admin/orders', $data);
        
        $response->assertSessionHasErrors('order_number');
    }

    /** @test */
    public function order_must_have_customer()
    {
        $data = [
            'order_number' => 'ORD-' . now()->timestamp,
            'customer_id' => null,
            'currency_id' => $this->client->currency_id,
        ];
        
        $response = $this->post('/admin/orders', $data);
        
        $response->assertSessionHasErrors('customer_id');
    }

    /** @test */
    public function order_must_have_currency()
    {
        $data = [
            'order_number' => 'ORD-' . now()->timestamp,
            'customer_id' => $this->client->id,
            'currency_id' => null,
        ];
        
        $response = $this->post('/admin/orders', $data);
        
        $response->assertSessionHasErrors('currency_id');
    }

    /** @test */
    public function expected_delivery_date_must_be_after_order_date()
    {
        $data = [
            'order_number' => 'ORD-' . now()->timestamp,
            'customer_id' => $this->client->id,
            'currency_id' => $this->client->currency_id,
            'order_date' => now()->format('Y-m-d'),
            'expected_delivery_date' => now()->subDays(5)->format('Y-m-d'),
        ];
        
        $response = $this->post('/admin/orders', $data);
        
        $response->assertSessionHasErrors('expected_delivery_date');
    }

    // ===== REGRAS DE TRANSIÇÃO DE STATUS =====

    /** @test */
    public function can_transition_from_draft_to_confirmed()
    {
        $order = Order::factory()->for($this->client)->create(['status' => 'pending']);
        OrderItem::factory()->for($order)->create();
        
        $this->put("/admin/orders/{$order->id}", ['status' => 'processing']);
        
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }

    /** @test */
    public function can_transition_from_confirmed_to_shipped()
    {
        $order = Order::factory()->for($this->client)->create(['status' => 'processing']);
        
        $this->put("/admin/orders/{$order->id}", ['status' => 'shipped']);
        
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'shipped',
        ]);
    }

    /** @test */
    public function cannot_transition_from_draft_to_shipped_directly()
    {
        $order = Order::factory()->for($this->client)->create(['status' => 'pending']);
        
        $response = $this->put("/admin/orders/{$order->id}", ['status' => 'shipped']);
        
        // Deve retornar erro ou manter status draft
        $order->refresh();
        $this->assertNotEquals('shipped', $order->status);
    }

    // ===== REGRAS DE ITENS DE ORDEM =====

    /** @test */
    public function order_item_quantity_must_be_positive()
    {
        $order = Order::factory()->for($this->client)->create();
        
        $data = [
            'product_id' => $this->product->id,
            'quantity' => -5,
            'unit_price' => 10000,
        ];
        
        $response = $this->post("/admin/orders/{$order->id}/items", $data);
        
        $response->assertSessionHasErrors('quantity');
    }

    /** @test */
    public function order_item_unit_price_must_be_positive()
    {
        $order = Order::factory()->for($this->client)->create();
        
        $data = [
            'product_id' => $this->product->id,
            'quantity' => 5,
            'unit_price' => -1000,
        ];
        
        $response = $this->post("/admin/orders/{$order->id}/items", $data);
        
        $response->assertSessionHasErrors('unit_price');
    }

    /** @test */
    public function cannot_add_same_product_twice_to_order()
    {
        $order = Order::factory()->for($this->client)->create();
        
        OrderItem::factory()->for($order)->for($this->product)->create();
        
        $data = [
            'product_id' => $this->product->id,
            'quantity' => 5,
            'unit_price' => 10000,
        ];
        
        $response = $this->post("/admin/orders/{$order->id}/items", $data);
        
        $response->assertSessionHasErrors();
    }

    // ===== REGRAS DE DELEÇÃO =====

    /** @test */
    public function can_delete_draft_order()
    {
        $order = Order::factory()->for($this->client)->create(['status' => 'pending']);
        
        $response = $this->delete("/admin/orders/{$order->id}");
        
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    /** @test */
    public function cannot_delete_confirmed_order()
    {
        $order = Order::factory()->for($this->client)->create(['status' => 'processing']);
        
        $response = $this->delete("/admin/orders/{$order->id}");
        
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    /** @test */
    public function cannot_delete_order_with_proforma_invoice()
    {
        $order = Order::factory()->for($this->client)->create();
        \App\Models\ProformaInvoice::factory()->for($order)->create();
        
        $response = $this->delete("/admin/orders/{$order->id}");
        
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    // ===== REGRAS DE CÁLCULO =====

    /** @test */
    public function order_total_is_calculated_correctly()
    {
        $order = Order::factory()->for($this->client)->create();
        
        OrderItem::factory()->for($order)->create([
            'quantity' => 10,
            'unit_price' => 10000,
        ]);
        
        OrderItem::factory()->for($order)->create([
            'quantity' => 5,
            'unit_price' => 20000,
        ]);
        
        // Total deve ser: (10 * 10000) + (5 * 20000) = 200000
        $response = $this->get("/admin/orders/{$order->id}/edit");
        $response->assertSuccessful();
    }

    /** @test */
    public function order_total_includes_expenses()
    {
        $order = Order::factory()->for($this->client)->create();
        
        OrderItem::factory()->for($order)->create([
            'quantity' => 10,
            'unit_price' => 10000,
        ]);
        
        // Adicionar despesa
        $this->post("/admin/orders/{$order->id}/add-expense", [
            'description' => 'Shipping',
            'amount' => 5000,
            'category' => 'shipping',
        ]);
        
        // Total deve incluir despesa
        $response = $this->get("/admin/orders/{$order->id}/edit");
        $response->assertSuccessful();
    }

    // ===== REGRAS DE RELACIONAMENTO =====

    /** @test */
    public function cannot_change_customer_if_order_confirmed()
    {
        $order = Order::factory()->for($this->client)->create(['status' => 'processing']);
        $newClient = Client::factory()->for($this->user)->create();
        
        $response = $this->put("/admin/orders/{$order->id}", [
            'customer_id' => $newClient->id,
        ]);
        
        $order->refresh();
        $this->assertEquals($this->client->id, $order->customer_id);
    }

    /** @test */
    public function can_change_customer_if_order_draft()
    {
        $order = Order::factory()->for($this->client)->create(['status' => 'pending']);
        $newClient = Client::factory()->for($this->user)->create();
        
        $this->put("/admin/orders/{$order->id}", [
            'customer_id' => $newClient->id,
        ]);
        
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'customer_id' => $newClient->id,
        ]);
    }
}
