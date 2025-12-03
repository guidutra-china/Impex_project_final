<?php

namespace Tests\Integration\Repositories;

use App\Models\SalesInvoice;
use App\Models\Order;
use App\Models\Client;
use App\Models\User;
use App\Repositories\SalesInvoiceRepository;
use Tests\TestCase;

class SalesInvoiceRepositoryTest extends TestCase
{
    private SalesInvoiceRepository $repository;
    private User $user;
    private Client $client;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = app(SalesInvoiceRepository::class);
        
        // Criar dados de teste
        $this->user = User::factory()->create();
        $this->client = Client::factory()->for($this->user)->create();
        $this->order = Order::factory()->for($this->client)->create();
    }

    // ===== TESTES CRUD =====

    /** @test */
    public function it_can_find_sales_invoice_by_id()
    {
        $invoice = SalesInvoice::factory()->for($this->order)->create();
        
        $found = $this->repository->findById($invoice->id);
        
        expect($found)->not->toBeNull();
        expect($found->id)->toBe($invoice->id);
    }

    /** @test */
    public function it_returns_null_when_sales_invoice_not_found()
    {
        $found = $this->repository->findById(99999);
        
        expect($found)->toBeNull();
    }

    /** @test */
    public function it_can_get_all_sales_invoices()
    {
        SalesInvoice::factory(3)->for($this->order)->create();
        
        $invoices = $this->repository->all();
        
        expect($invoices->count())->toBeGreaterThanOrEqual(3);
    }

    /** @test */
    public function it_can_create_sales_invoice()
    {
        $data = [
            'order_id' => $this->order->id,
            'invoice_number' => 'SI-' . now()->timestamp,
            'status' => 'pending',
            'total_amount' => 100000,
            'currency_id' => 1,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'created_by' => $this->user->id,
        ];
        
        $invoice = $this->repository->create($data);
        
        expect($invoice)->toBeInstanceOf(SalesInvoice::class);
        expect($invoice->status)->toBe('draft');
        expect($invoice->total_amount)->toBe(100000);
    }

    /** @test */
    public function it_can_update_sales_invoice()
    {
        $invoice = SalesInvoice::factory()->for($this->order)->create();
        
        $updated = $this->repository->update($invoice->id, [
            'status' => 'sent',
            'total_amount' => 150000,
        ]);
        
        expect($updated)->toBeTrue();
        expect($invoice->fresh()->status)->toBe('sent');
        expect($invoice->fresh()->total_amount)->toBe(150000);
    }

    /** @test */
    public function it_can_delete_sales_invoice()
    {
        $invoice = SalesInvoice::factory()->for($this->order)->create();
        
        $deleted = $this->repository->delete($invoice->id);
        
        expect($deleted)->toBeTrue();
        expect(SalesInvoice::find($invoice->id))->toBeNull();
    }

    // ===== TESTES DE FILTROS =====

    /** @test */
    public function it_can_get_sales_invoices_by_status()
    {
        SalesInvoice::factory(2)->for($this->order)->create(['status' => 'pending']);
        SalesInvoice::factory(1)->for($this->order)->create(['status' => 'sent']);
        
        $drafts = $this->repository->getByStatus('draft');
        
        expect($drafts->count())->toBeGreaterThanOrEqual(2);
        expect($drafts->every(fn($i) => $i->status === 'draft'))->toBeTrue();
    }

    /** @test */
    public function it_can_get_sales_invoices_by_customer()
    {
        SalesInvoice::factory(3)->for($this->order)->create();
        
        $invoices = $this->repository->getByCustomer($this->client->id);
        
        expect($invoices->count())->toBeGreaterThanOrEqual(3);
    }

    // ===== TESTES DE CÁLCULOS ESPECÍFICOS =====

    /** @test */
    public function it_can_get_total_pending_amount()
    {
        SalesInvoice::factory(2)->for($this->order)->create([
            'status' => 'pending',
            'total_amount' => 100000,
        ]);
        
        $total = $this->repository->getTotalPending();
        
        expect($total)->toBeGreaterThanOrEqual(200000);
    }

    /** @test */
    public function it_can_get_total_overdue_amount()
    {
        SalesInvoice::factory(2)->for($this->order)->create([
            'status' => 'overdue',
            'total_amount' => 100000,
        ]);
        
        $total = $this->repository->getTotalOverdue();
        
        expect($total)->toBeGreaterThanOrEqual(200000);
    }

    /** @test */
    public function it_can_get_this_month_total()
    {
        SalesInvoice::factory(2)->for($this->order)->create([
            'total_amount' => 100000,
            'invoice_date' => now(),
        ]);
        
        $total = $this->repository->getThisMonthTotal();
        
        expect($total)->toBeGreaterThanOrEqual(200000);
    }

    /** @test */
    public function it_can_calculate_sales_trend()
    {
        // Criar invoices para diferentes meses
        SalesInvoice::factory(2)->for($this->order)->create([
            'total_amount' => 100000,
            'invoice_date' => now()->subMonth(),
        ]);
        
        SalesInvoice::factory(3)->for($this->order)->create([
            'total_amount' => 100000,
            'invoice_date' => now(),
        ]);
        
        $trend = $this->repository->calculateSalesTrend();
        
        expect($trend)->toBeArray();
        expect($trend)->toHaveKeys(['current_month', 'previous_month', 'growth_percentage']);
    }

    // ===== TESTES DE BUSCA =====

    /** @test */
    public function it_can_search_sales_invoices()
    {
        $invoice = SalesInvoice::factory()
            ->for($this->order)
            ->create(['invoice_number' => 'SI-UNIQUE-12345']);
        
        $results = $this->repository->searchInvoices('SI-UNIQUE');
        
        expect($results->pluck('id')->contains($invoice->id))->toBeTrue();
    }

    // ===== TESTES DE ESTATÍSTICAS =====

    /** @test */
    public function it_can_get_statistics()
    {
        SalesInvoice::factory(5)->for($this->order)->create();
        
        $stats = $this->repository->getStatistics();
        
        expect($stats)->toHaveKeys([
            'total_invoices',
            'pending_invoices',
            'paid_invoices',
            'overdue_invoices',
            'total_amount',
            'average_amount',
        ]);
        expect($stats['total_invoices'])->toBeGreaterThanOrEqual(5);
    }

    // ===== TESTES DE QUERIES =====

    /** @test */
    public function it_can_get_query_builder()
    {
        $query = $this->repository->getQuery();
        
        expect($query)->not->toBeNull();
        expect($query->count())->toBeGreaterThanOrEqual(0);
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
    public function it_handles_large_amounts_correctly()
    {
        $invoice = SalesInvoice::factory()
            ->for($this->order)
            ->create(['total_amount' => 999999999999]);
        
        $found = $this->repository->findById($invoice->id);
        
        expect($found->total_amount)->toBe(999999999999);
    }

    /** @test */
    public function it_can_count_invoices_by_status()
    {
        SalesInvoice::factory(3)->for($this->order)->create(['status' => 'pending']);
        
        $count = $this->repository->countByStatus('draft');
        
        expect($count)->toBeGreaterThanOrEqual(3);
    }

    /** @test */
    public function it_can_get_total_amount_by_status()
    {
        SalesInvoice::factory(2)->for($this->order)->create([
            'status' => 'paid',
            'total_amount' => 100000,
        ]);
        
        $total = $this->repository->getTotalByStatus('paid');
        
        expect($total)->toBeGreaterThanOrEqual(200000);
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $this->expectException(\Exception::class);
        
        $this->repository->create([
            'invoice_number' => 'SI-123',
            // Missing required fields
        ]);
    }

    /** @test */
    public function it_can_handle_multiple_invoices_per_order()
    {
        SalesInvoice::factory(3)->for($this->order)->create();
        
        $invoices = $this->repository->getByOrder($this->order->id);
        
        expect($invoices->count())->toBe(3);
    }

    /** @test */
    public function it_can_get_invoices_by_date_range()
    {
        $startDate = now()->subDays(10);
        $endDate = now()->addDays(10);
        
        SalesInvoice::factory(2)->for($this->order)->create([
            'invoice_date' => now(),
        ]);
        
        $invoices = $this->repository->getByDateRange($startDate, $endDate);
        
        expect($invoices->count())->toBeGreaterThanOrEqual(2);
    }

    /** @test */
    public function it_can_get_overdue_invoices()
    {
        SalesInvoice::factory(2)->for($this->order)->create([
            'status' => 'overdue',
        ]);
        
        SalesInvoice::factory(1)->for($this->order)->create([
            'status' => 'pending',
        ]);
        
        $overdue = $this->repository->getOverdueInvoices();
        
        expect($overdue->count())->toBeGreaterThanOrEqual(2);
        expect($overdue->every(fn($i) => $i->status === 'overdue'))->toBeTrue();
    }
}
