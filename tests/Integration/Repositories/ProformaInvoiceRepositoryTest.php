<?php

namespace Tests\Integration\Repositories;

use App\Models\ProformaInvoice;
use App\Models\Order;
use App\Models\Client;
use App\Models\User;
use App\Repositories\ProformaInvoiceRepository;
use Tests\TestCase;

class ProformaInvoiceRepositoryTest extends TestCase
{
    private ProformaInvoiceRepository $repository;
    private User $user;
    private Client $client;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = app(ProformaInvoiceRepository::class);
        
        // Criar dados de teste
        $this->user = User::factory()->create();
        $this->client = Client::factory()->for($this->user)->create();
        $this->order = Order::factory()->for($this->client)->create();
    }

    // ===== TESTES CRUD =====

    /** @test */
    public function it_can_find_proforma_invoice_by_id()
    {
        $invoice = ProformaInvoice::factory()->for($this->order)->create();
        
        $found = $this->repository->findById($invoice->id);
        
        expect($found)->not->toBeNull();
        expect($found->id)->toBe($invoice->id);
    }

    /** @test */
    public function it_returns_null_when_proforma_invoice_not_found()
    {
        $found = $this->repository->findById(99999);
        
        expect($found)->toBeNull();
    }

    /** @test */
    public function it_can_get_all_proforma_invoices()
    {
        ProformaInvoice::factory(3)->for($this->order)->create();
        
        $invoices = $this->repository->all();
        
        expect($invoices->count())->toBeGreaterThanOrEqual(3);
    }

    /** @test */
    public function it_can_create_proforma_invoice()
    {
        $data = [
            'order_id' => $this->order->id,
            'proforma_number' => 'PI-' . now()->timestamp,
            'status' => 'pending',
            'total_amount' => 100000,
            'currency_id' => 1,
            'issue_date' => now(),
            'created_by' => $this->user->id,
        ];
        
        $invoice = $this->repository->create($data);
        
        expect($invoice)->toBeInstanceOf(ProformaInvoice::class);
        expect($invoice->status)->toBe('draft');
        expect($invoice->total_amount)->toBe(100000);
    }

    /** @test */
    public function it_can_update_proforma_invoice()
    {
        $invoice = ProformaInvoice::factory()->for($this->order)->create();
        
        $updated = $this->repository->update($invoice->id, [
            'status' => 'sent',
            'total_amount' => 150000,
        ]);
        
        expect($updated)->toBeTrue();
        expect($invoice->fresh()->status)->toBe('approved');
        expect($invoice->fresh()->total_amount)->toBe(150000);
    }

    /** @test */
    public function it_can_delete_proforma_invoice()
    {
        $invoice = ProformaInvoice::factory()->for($this->order)->create();
        
        $deleted = $this->repository->delete($invoice->id);
        
        expect($deleted)->toBeTrue();
        expect(ProformaInvoice::find($invoice->id))->toBeNull();
    }

    // ===== TESTES DE FILTROS =====

    /** @test */
    public function it_can_get_proforma_invoices_by_status()
    {
        ProformaInvoice::factory(2)->for($this->order)->create(['status' => 'pending']);
        ProformaInvoice::factory(1)->for($this->order)->create(['status' => 'sent']);
        
        $drafts = $this->repository->getByStatus('draft');
        
        expect($drafts->count())->toBeGreaterThanOrEqual(2);
        expect($drafts->every(fn($i) => $i->status === 'draft'))->toBeTrue();
    }

    /** @test */
    public function it_can_get_proforma_invoices_by_client()
    {
        ProformaInvoice::factory(3)->for($this->order)->create();
        
        $invoices = $this->repository->getByClient($this->client->id);
        
        expect($invoices->count())->toBeGreaterThanOrEqual(3);
    }

    /** @test */
    public function it_can_get_proforma_invoices_by_order()
    {
        ProformaInvoice::factory(2)->for($this->order)->create();
        
        $invoices = $this->repository->getByOrder($this->order->id);
        
        expect($invoices->count())->toBe(2);
        expect($invoices->every(fn($i) => $i->order_id === $this->order->id))->toBeTrue();
    }

    // ===== TESTES DE TRANSIÇÕES DE ESTADO =====

    /** @test */
    public function it_can_approve_proforma_invoice()
    {
        $invoice = ProformaInvoice::factory()
            ->for($this->order)
            ->create(['status' => 'pending']);
        
        $result = $this->repository->approve($invoice->id);
        
        expect($result)->toBeTrue();
        expect($invoice->fresh()->status)->toBe('approved');
    }

    /** @test */
    public function it_can_reject_proforma_invoice()
    {
        $invoice = ProformaInvoice::factory()
            ->for($this->order)
            ->create(['status' => 'pending']);
        
        $result = $this->repository->reject($invoice->id);
        
        expect($result)->toBeTrue();
        expect($invoice->fresh()->status)->toBe('rejected');
    }

    /** @test */
    public function it_can_mark_proforma_invoice_as_sent()
    {
        $invoice = ProformaInvoice::factory()
            ->for($this->order)
            ->create(['status' => 'pending']);
        
        $result = $this->repository->markAsSent($invoice->id);
        
        expect($result)->toBeTrue();
        expect($invoice->fresh()->status)->toBe('sent');
    }

    /** @test */
    public function it_can_mark_deposit_received()
    {
        $invoice = ProformaInvoice::factory()
            ->for($this->order)
            ->create(['status' => 'sent']);
        
        $result = $this->repository->markDepositReceived($invoice->id);
        
        expect($result)->toBeTrue();
        expect($invoice->fresh()->status)->toBe('deposit_received');
    }

    // ===== TESTES DE BUSCA =====

    /** @test */
    public function it_can_search_proforma_invoices()
    {
        $invoice = ProformaInvoice::factory()
            ->for($this->order)
            ->create(['proforma_number' => 'PI-UNIQUE-12345']);
        
        $results = $this->repository->searchInvoices('PI-UNIQUE');
        
        expect($results->pluck('id')->contains($invoice->id))->toBeTrue();
    }

    // ===== TESTES DE ESTATÍSTICAS =====

    /** @test */
    public function it_can_get_statistics()
    {
        ProformaInvoice::factory(5)->for($this->order)->create();
        
        $stats = $this->repository->getStatistics();
        
        expect($stats)->toHaveKeys([
            'total_invoices',
            'draft_invoices',
            'approved_invoices',
            'sent_invoices',
            'total_amount',
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

    /** @test */
    public function it_can_get_items_query()
    {
        $invoice = ProformaInvoice::factory()->for($this->order)->create();
        
        $query = $this->repository->getItemsQuery($invoice->id);
        
        expect($query)->not->toBeNull();
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
    public function it_prevents_invalid_status_transitions()
    {
        $invoice = ProformaInvoice::factory()
            ->for($this->order)
            ->create(['status' => 'rejected']);
        
        // Tentar aprovar uma invoice rejeitada deve falhar ou ser ignorado
        $result = $this->repository->approve($invoice->id);
        
        // Dependendo da implementação, pode retornar false ou ignorar
        expect($result)->toBeFalsy();
    }

    /** @test */
    public function it_can_handle_multiple_invoices_per_order()
    {
        ProformaInvoice::factory(3)->for($this->order)->create();
        
        $invoices = $this->repository->getByOrder($this->order->id);
        
        expect($invoices->count())->toBe(3);
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $this->expectException(\Exception::class);
        
        $this->repository->create([
            'proforma_number' => 'PI-123',
            // Missing required fields
        ]);
    }

    /** @test */
    public function it_can_count_invoices_by_status()
    {
        ProformaInvoice::factory(3)->for($this->order)->create(['status' => 'pending']);
        
        $count = $this->repository->countByStatus('draft');
        
        expect($count)->toBeGreaterThanOrEqual(3);
    }

    /** @test */
    public function it_can_get_total_amount_by_status()
    {
        ProformaInvoice::factory(2)->for($this->order)->create([
            'status' => 'sent',
            'total_amount' => 100000,
        ]);
        
        $total = $this->repository->getTotalByStatus('approved');
        
        expect($total)->toBeGreaterThanOrEqual(200000);
    }
}
