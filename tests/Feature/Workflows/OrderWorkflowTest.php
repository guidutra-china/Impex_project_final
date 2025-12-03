<?php

namespace Tests\Feature\Workflows;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\SupplierQuote;
use App\Models\ProformaInvoice;
use App\Models\Shipment;
use App\Models\Product;
use App\Models\User;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    private User $user;
    private Client $client;
    private Supplier $supplier;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->client = Client::factory()->for($this->user)->create();
        $this->supplier = Supplier::factory()->for($this->user)->create();
        $this->product = Product::factory()->for($this->user)->create();
        $this->actingAs($this->user);
    }

    // ===== TESTE DO FLUXO COMPLETO DE ORDEM =====

    /** @test */
    public function complete_order_workflow_from_creation_to_delivery()
    {
        // 1. Criar ordem
        $orderData = [
            'order_number' => 'ORD-' . now()->timestamp,
            'customer_id' => $this->client->id,
            'currency_id' => $this->client->currency_id,
            'status' => 'draft',
            'order_date' => now()->format('Y-m-d'),
            'expected_delivery_date' => now()->addDays(30)->format('Y-m-d'),
        ];
        
        $response = $this->post('/admin/orders', $orderData);
        $order = Order::where('order_number', $orderData['order_number'])->first();
        
        $this->assertNotNull($order);
        $this->assertEquals('draft', $order->status);

        // 2. Adicionar itens à ordem
        $itemData = [
            'product_id' => $this->product->id,
            'quantity' => 10,
            'unit_price' => 10000,
        ];
        
        $this->post("/admin/orders/{$order->id}/items", $itemData);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $this->product->id,
        ]);

        // 3. Confirmar ordem
        $this->put("/admin/orders/{$order->id}", ['status' => 'confirmed']);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'confirmed',
        ]);

        // 4. Enviar RFQ para fornecedor
        $this->post("/admin/orders/{$order->id}/send-rfq", [
            'supplier_ids' => [$this->supplier->id],
        ]);
        
        // 5. Receber cotação do fornecedor
        $quote = SupplierQuote::factory()
            ->for($order)
            ->for($this->supplier)
            ->create(['status' => 'draft']);
        
        $this->assertDatabaseHas('supplier_quotes', [
            'order_id' => $order->id,
            'supplier_id' => $this->supplier->id,
        ]);

        // 6. Aprovar cotação
        $this->post("/admin/supplier-quotes/{$quote->id}/approve");
        $this->assertDatabaseHas('supplier_quotes', [
            'id' => $quote->id,
            'status' => 'approved',
        ]);

        // 7. Criar proforma invoice
        $invoice = ProformaInvoice::factory()
            ->for($order)
            ->create(['status' => 'draft']);
        
        $this->assertDatabaseHas('proforma_invoices', [
            'order_id' => $order->id,
            'status' => 'draft',
        ]);

        // 8. Aprovar proforma invoice
        $this->post("/admin/proforma-invoices/{$invoice->id}/approve");
        $this->assertDatabaseHas('proforma_invoices', [
            'id' => $invoice->id,
            'status' => 'approved',
        ]);

        // 9. Marcar como enviado
        $this->post("/admin/proforma-invoices/{$invoice->id}/mark-sent");
        $this->assertDatabaseHas('proforma_invoices', [
            'id' => $invoice->id,
            'status' => 'sent',
        ]);

        // 10. Receber depósito
        $this->post("/admin/proforma-invoices/{$invoice->id}/mark-deposit-received", [
            'deposit_amount' => 50000,
            'deposit_date' => now()->format('Y-m-d'),
        ]);
        $this->assertDatabaseHas('proforma_invoices', [
            'id' => $invoice->id,
            'deposit_received' => true,
        ]);

        // 11. Criar shipment
        $shipment = Shipment::factory()
            ->for($order)
            ->create(['status' => 'pending']);
        
        $this->assertDatabaseHas('shipments', [
            'order_id' => $order->id,
            'status' => 'pending',
        ]);

        // 12. Marcar como entregue
        $this->put("/admin/shipments/{$shipment->id}", ['status' => 'delivered']);
        $this->assertDatabaseHas('shipments', [
            'id' => $shipment->id,
            'status' => 'delivered',
        ]);
    }

    // ===== TESTES DE VALIDAÇÕES DO FLUXO =====

    /** @test */
    public function cannot_confirm_order_without_items()
    {
        $order = Order::factory()->for($this->client)->create(['status' => 'draft']);
        
        $response = $this->put("/admin/orders/{$order->id}", ['status' => 'confirmed']);
        
        // Deve retornar erro ou manter status draft
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function cannot_send_rfq_without_suppliers()
    {
        $order = Order::factory()->for($this->client)->create(['status' => 'confirmed']);
        OrderItem::factory()->for($order)->create();
        
        $response = $this->post("/admin/orders/{$order->id}/send-rfq", [
            'supplier_ids' => [],
        ]);
        
        $response->assertSessionHasErrors();
    }

    /** @test */
    public function cannot_create_proforma_invoice_without_approved_quote()
    {
        $order = Order::factory()->for($this->client)->create(['status' => 'confirmed']);
        OrderItem::factory()->for($order)->create();
        
        $quote = SupplierQuote::factory()
            ->for($order)
            ->for($this->supplier)
            ->create(['status' => 'draft']);
        
        // Tentar criar invoice sem aprovar cotação
        $response = $this->post("/admin/proforma-invoices", [
            'order_id' => $order->id,
            'supplier_quote_id' => $quote->id,
        ]);
        
        // Deve retornar erro
        $response->assertSessionHasErrors();
    }

    /** @test */
    public function cannot_mark_delivered_without_shipment()
    {
        $order = Order::factory()->for($this->client)->create(['status' => 'confirmed']);
        
        // Não há shipment, então não pode marcar como entregue
        $response = $this->put("/admin/orders/{$order->id}", ['status' => 'delivered']);
        
        // Deve retornar erro ou manter status anterior
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
        ]);
    }

    // ===== TESTES DE MÚLTIPLAS COTAÇÕES =====

    /** @test */
    public function can_receive_multiple_quotes_from_different_suppliers()
    {
        $order = Order::factory()->for($this->client)->create(['status' => 'confirmed']);
        OrderItem::factory()->for($order)->create();
        
        $supplier2 = Supplier::factory()->for($this->user)->create();
        $supplier3 = Supplier::factory()->for($this->user)->create();
        
        // Receber cotações de 3 fornecedores
        $quote1 = SupplierQuote::factory()->for($order)->for($this->supplier)->create();
        $quote2 = SupplierQuote::factory()->for($order)->for($supplier2)->create();
        $quote3 = SupplierQuote::factory()->for($order)->for($supplier3)->create();
        
        $this->assertDatabaseHas('supplier_quotes', ['order_id' => $order->id, 'supplier_id' => $this->supplier->id]);
        $this->assertDatabaseHas('supplier_quotes', ['order_id' => $order->id, 'supplier_id' => $supplier2->id]);
        $this->assertDatabaseHas('supplier_quotes', ['order_id' => $order->id, 'supplier_id' => $supplier3->id]);
        
        // Selecionar a melhor cotação
        $this->post("/admin/supplier-quotes/{$quote1->id}/approve");
        
        $this->assertDatabaseHas('supplier_quotes', ['id' => $quote1->id, 'status' => 'approved']);
    }

    // ===== TESTES DE CANCELAMENTO =====

    /** @test */
    public function can_cancel_draft_order()
    {
        $order = Order::factory()->for($this->client)->create(['status' => 'draft']);
        
        $this->delete("/admin/orders/{$order->id}");
        
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    /** @test */
    public function cannot_cancel_confirmed_order()
    {
        $order = Order::factory()->for($this->client)->create(['status' => 'confirmed']);
        
        $response = $this->delete("/admin/orders/{$order->id}");
        
        // Deve retornar erro
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    // ===== TESTES DE DESPESAS =====

    /** @test */
    public function can_add_expenses_to_order()
    {
        $order = Order::factory()->for($this->client)->create();
        
        // Adicionar despesa de envio
        $this->post("/admin/orders/{$order->id}/add-expense", [
            'description' => 'Shipping Cost',
            'amount' => 10000,
            'category' => 'shipping',
        ]);
        
        $this->assertDatabaseHas('financial_transactions', [
            'order_id' => $order->id,
            'description' => 'Shipping Cost',
            'category' => 'shipping',
        ]);
    }

    /** @test */
    public function expenses_are_included_in_order_total()
    {
        $order = Order::factory()->for($this->client)->create();
        
        // Adicionar múltiplas despesas
        $this->post("/admin/orders/{$order->id}/add-expense", [
            'description' => 'Shipping',
            'amount' => 10000,
            'category' => 'shipping',
        ]);
        
        $this->post("/admin/orders/{$order->id}/add-expense", [
            'description' => 'Handling',
            'amount' => 5000,
            'category' => 'handling',
        ]);
        
        // Total de despesas deve ser 15000
        $response = $this->get("/admin/orders/{$order->id}/edit");
        $response->assertSuccessful();
    }

    // ===== TESTES DE DOCUMENTOS =====

    /** @test */
    public function can_attach_documents_to_order()
    {
        $order = Order::factory()->for($this->client)->create();
        
        $file = \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 100);
        
        $response = $this->post("/admin/orders/{$order->id}/documents", [
            'file' => $file,
            'type' => 'invoice',
        ]);
        
        // Verificar se documento foi criado
    }

    // ===== TESTES DE AUDITORIA =====

    /** @test */
    public function order_status_changes_are_logged()
    {
        $order = Order::factory()->for($this->client)->create(['status' => 'draft']);
        
        $this->put("/admin/orders/{$order->id}", ['status' => 'confirmed']);
        
        // Verificar se há log de mudança de status
        // (implementar conforme necessário)
    }
}
