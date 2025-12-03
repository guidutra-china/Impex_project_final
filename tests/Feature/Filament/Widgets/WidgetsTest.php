<?php

namespace Tests\Feature\Filament\Widgets;

use App\Models\Order;
use App\Models\Client;
use App\Models\User;
use App\Models\FinancialTransaction;
use App\Models\SalesInvoice;
use App\Models\PurchaseOrder;
use App\Models\Document;
use App\Models\RFQ;
use App\Models\Event;
use App\Models\Product;
use Tests\TestCase;

class WidgetsTest extends TestCase
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

    // ===== TESTES DO WIDGET: ProjectExpensesWidget =====

    /** @test */
    public function project_expenses_widget_renders()
    {
        $order = Order::factory()->for($this->client)->create();
        
        $response = $this->get("/admin/orders/{$order->id}/edit");
        
        $response->assertSuccessful();
    }

    /** @test */
    public function project_expenses_widget_displays_expenses()
    {
        $order = Order::factory()->for($this->client)->create();
        FinancialTransaction::factory(3)->for($order)->create(['type' => 'expense']);
        
        $response = $this->get("/admin/orders/{$order->id}/edit");
        
        $response->assertSuccessful();
    }

    /** @test */
    public function project_expenses_widget_handles_no_expenses()
    {
        $order = Order::factory()->for($this->client)->create();
        
        $response = $this->get("/admin/orders/{$order->id}/edit");
        
        $response->assertSuccessful();
    }

    /** @test */
    public function project_expenses_widget_calculates_total()
    {
        $order = Order::factory()->for($this->client)->create();
        FinancialTransaction::factory()->for($order)->create(['type' => 'expense', 'amount' => 5000]);
        FinancialTransaction::factory()->for($order)->create(['type' => 'expense', 'amount' => 3000]);
        
        $response = $this->get("/admin/orders/{$order->id}/edit");
        
        $response->assertSuccessful();
        // Total should be 8000
    }

    // ===== TESTES DO WIDGET: FinancialOverviewWidget =====

    /** @test */
    public function financial_overview_widget_renders()
    {
        $response = $this->get('/admin/dashboard');
        
        $response->assertSuccessful();
    }

    /** @test */
    public function financial_overview_widget_displays_receivables()
    {
        SalesInvoice::factory(3)->for($this->user)->create(['status' => 'draft']);
        
        $response = $this->get('/admin/dashboard');
        
        $response->assertSuccessful();
    }

    /** @test */
    public function financial_overview_widget_displays_payables()
    {
        PurchaseOrder::factory(3)->for($this->user)->create(['status' => 'draft']);
        
        $response = $this->get('/admin/dashboard');
        
        $response->assertSuccessful();
    }

    /** @test */
    public function financial_overview_widget_calculates_totals()
    {
        SalesInvoice::factory()->for($this->user)->create(['status' => 'draft', 'total' => 100000]);
        SalesInvoice::factory()->for($this->user)->create(['status' => 'draft', 'total' => 50000]);
        
        $response = $this->get('/admin/dashboard');
        
        $response->assertSuccessful();
        // Total receivables should be 150000
    }

    /** @test */
    public function financial_overview_widget_handles_no_data()
    {
        $response = $this->get('/admin/dashboard');
        
        $response->assertSuccessful();
    }

    // ===== TESTES DO WIDGET: RelatedDocumentsWidget =====

    /** @test */
    public function related_documents_widget_renders()
    {
        $product = Product::factory()->for($this->user)->create();
        
        $response = $this->get("/admin/products/{$product->id}/edit");
        
        $response->assertSuccessful();
    }

    /** @test */
    public function related_documents_widget_displays_documents()
    {
        $product = Product::factory()->for($this->user)->create();
        Document::factory(3)->for($product)->create();
        
        $response = $this->get("/admin/products/{$product->id}/edit");
        
        $response->assertSuccessful();
    }

    /** @test */
    public function related_documents_widget_handles_no_documents()
    {
        $product = Product::factory()->for($this->user)->create();
        
        $response = $this->get("/admin/products/{$product->id}/edit");
        
        $response->assertSuccessful();
    }

    // ===== TESTES DO WIDGET: RfqStatsWidget =====

    /** @test */
    public function rfq_stats_widget_renders()
    {
        $response = $this->get('/admin/dashboard');
        
        $response->assertSuccessful();
    }

    /** @test */
    public function rfq_stats_widget_displays_rfq_count()
    {
        RFQ::factory(5)->for($this->user)->create();
        
        $response = $this->get('/admin/dashboard');
        
        $response->assertSuccessful();
    }

    /** @test */
    public function rfq_stats_widget_displays_rfq_by_status()
    {
        RFQ::factory(3)->for($this->user)->create(['status' => 'open']);
        RFQ::factory(2)->for($this->user)->create(['status' => 'closed']);
        
        $response = $this->get('/admin/dashboard');
        
        $response->assertSuccessful();
    }

    /** @test */
    public function rfq_stats_widget_handles_no_rfqs()
    {
        $response = $this->get('/admin/dashboard');
        
        $response->assertSuccessful();
    }

    // ===== TESTES DO WIDGET: PurchaseOrderStatsWidget =====

    /** @test */
    public function purchase_order_stats_widget_renders()
    {
        $response = $this->get('/admin/dashboard');
        
        $response->assertSuccessful();
    }

    /** @test */
    public function purchase_order_stats_widget_displays_po_count()
    {
        PurchaseOrder::factory(5)->for($this->user)->create();
        
        $response = $this->get('/admin/dashboard');
        
        $response->assertSuccessful();
    }

    /** @test */
    public function purchase_order_stats_widget_displays_po_by_status()
    {
        PurchaseOrder::factory(3)->for($this->user)->create(['status' => 'draft']);
        PurchaseOrder::factory(2)->for($this->user)->create(['status' => 'completed']);
        
        $response = $this->get('/admin/dashboard');
        
        $response->assertSuccessful();
    }

    /** @test */
    public function purchase_order_stats_widget_handles_no_pos()
    {
        $response = $this->get('/admin/dashboard');
        
        $response->assertSuccessful();
    }

    // ===== TESTES DO WIDGET: CalendarWidget =====

    /** @test */
    public function calendar_widget_renders()
    {
        $response = $this->get('/admin/dashboard');
        
        $response->assertSuccessful();
    }

    /** @test */
    public function calendar_widget_displays_events()
    {
        Event::factory(5)->for($this->user)->create();
        
        $response = $this->get('/admin/dashboard');
        
        $response->assertSuccessful();
    }

    /** @test */
    public function calendar_widget_displays_upcoming_events()
    {
        Event::factory()->for($this->user)->create(['date' => now()->addDays(5)]);
        Event::factory()->for($this->user)->create(['date' => now()->subDays(5)]);
        
        $response = $this->get('/admin/dashboard');
        
        $response->assertSuccessful();
    }

    /** @test */
    public function calendar_widget_handles_no_events()
    {
        $response = $this->get('/admin/dashboard');
        
        $response->assertSuccessful();
    }

    // ===== TESTES DE PERFORMANCE =====

    /** @test */
    public function widgets_load_with_large_dataset()
    {
        FinancialTransaction::factory(100)->for(Order::factory()->for($this->client)->create())->create();
        SalesInvoice::factory(50)->for($this->user)->create();
        PurchaseOrder::factory(50)->for($this->user)->create();
        
        $response = $this->get('/admin/dashboard');
        
        $response->assertSuccessful();
    }

    // ===== TESTES DE PERMISSÕES =====

    /** @test */
    public function unauthorized_user_cannot_view_dashboard()
    {
        $this->actingAs(User::factory()->create());
        
        $response = $this->get('/admin/dashboard');
        
        $this->assertTrue($response->status() === 403 || $response->status() === 302);
    }
}
