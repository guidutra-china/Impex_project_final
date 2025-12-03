<?php

namespace Tests\Integration\Repositories;

use App\Models\FinancialTransaction;
use App\Models\Project;
use App\Models\User;
use App\Repositories\FinancialTransactionRepository;
use Tests\TestCase;

class FinancialTransactionRepositoryTest extends TestCase
{
    private FinancialTransactionRepository $repository;
    private User $user;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = app(FinancialTransactionRepository::class);
        
        // Criar dados de teste
        $this->user = User::factory()->create();
        $this->project = Project::factory()->for($this->user)->create();
    }

    // ===== TESTES CRUD =====

    /** @test */
    public function it_can_find_transaction_by_id()
    {
        $transaction = FinancialTransaction::factory()->for($this->project)->create();
        
        $found = $this->repository->findById($transaction->id);
        
        expect($found)->not->toBeNull();
        expect($found->id)->toBe($transaction->id);
    }

    /** @test */
    public function it_returns_null_when_transaction_not_found()
    {
        $found = $this->repository->findById(99999);
        
        expect($found)->toBeNull();
    }

    /** @test */
    public function it_can_get_all_transactions()
    {
        FinancialTransaction::factory(3)->for($this->project)->create();
        
        $transactions = $this->repository->all();
        
        expect($transactions->count())->toBeGreaterThanOrEqual(3);
    }

    /** @test */
    public function it_can_create_transaction()
    {
        $data = [
            'project_id' => $this->project->id,
            'transaction_number' => 'FT-' . now()->timestamp,
            'type' => 'payable',
            'category' => 'expense',
            'description' => 'Test Transaction',
            'amount' => 100000, // em centavos
            'currency_id' => 1,
            'status' => 'pending',
            'due_date' => now()->addDays(30),
            'created_by' => $this->user->id,
        ];
        
        $transaction = $this->repository->create($data);
        
        expect($transaction)->toBeInstanceOf(FinancialTransaction::class);
        expect($transaction->type)->toBe('payable');
        expect($transaction->status)->toBe('pending');
        expect($transaction->amount)->toBe(100000);
    }

    /** @test */
    public function it_can_update_transaction()
    {
        $transaction = FinancialTransaction::factory()->for($this->project)->create();
        
        $updated = $this->repository->update($transaction->id, [
            'status' => 'paid',
            'paid_amount' => $transaction->amount,
        ]);
        
        expect($updated)->toBeTrue();
        expect($transaction->fresh()->status)->toBe('paid');
    }

    /** @test */
    public function it_can_delete_transaction()
    {
        $transaction = FinancialTransaction::factory()->for($this->project)->create();
        
        $deleted = $this->repository->delete($transaction->id);
        
        expect($deleted)->toBeTrue();
        expect(FinancialTransaction::find($transaction->id))->toBeNull();
    }

    // ===== TESTES DE FILTROS =====

    /** @test */
    public function it_can_get_transactions_by_status()
    {
        FinancialTransaction::factory(2)->for($this->project)->create(['status' => 'pending']);
        FinancialTransaction::factory(1)->for($this->project)->create(['status' => 'paid']);
        
        $pending = $this->repository->getByStatus('pending');
        
        expect($pending->count())->toBeGreaterThanOrEqual(2);
        expect($pending->every(fn($t) => $t->status === 'pending'))->toBeTrue();
    }

    /** @test */
    public function it_can_get_transactions_by_type()
    {
        FinancialTransaction::factory(2)->for($this->project)->create(['type' => 'payable']);
        FinancialTransaction::factory(1)->for($this->project)->create(['type' => 'receivable']);
        
        $payable = $this->repository->getByType('payable');
        
        expect($payable->count())->toBeGreaterThanOrEqual(2);
        expect($payable->every(fn($t) => $t->type === 'payable'))->toBeTrue();
    }

    /** @test */
    public function it_can_get_transactions_by_category()
    {
        FinancialTransaction::factory(2)->for($this->project)->create(['category' => 'expense']);
        FinancialTransaction::factory(1)->for($this->project)->create(['category' => 'revenue']);
        
        $expenses = $this->repository->getByCategory('expense');
        
        expect($expenses->count())->toBeGreaterThanOrEqual(2);
        expect($expenses->every(fn($t) => $t->category === 'expense'))->toBeTrue();
    }

    /** @test */
    public function it_can_get_transactions_by_project()
    {
        FinancialTransaction::factory(3)->for($this->project)->create();
        
        $transactions = $this->repository->getByProject($this->project->id);
        
        expect($transactions->count())->toBeGreaterThanOrEqual(3);
        expect($transactions->every(fn($t) => $t->project_id === $this->project->id))->toBeTrue();
    }

    // ===== TESTES DE CÁLCULOS =====

    /** @test */
    public function it_can_get_total_by_status()
    {
        FinancialTransaction::factory(2)->for($this->project)->create([
            'status' => 'pending',
            'amount' => 100000,
        ]);
        
        $total = $this->repository->getTotalByStatus('pending');
        
        expect($total)->toBeGreaterThanOrEqual(200000);
    }

    /** @test */
    public function it_can_get_total_by_type()
    {
        FinancialTransaction::factory(2)->for($this->project)->create([
            'type' => 'payable',
            'amount' => 100000,
        ]);
        
        $total = $this->repository->getTotalByType('payable');
        
        expect($total)->toBeGreaterThanOrEqual(200000);
    }

    /** @test */
    public function it_can_count_by_status()
    {
        FinancialTransaction::factory(3)->for($this->project)->create(['status' => 'pending']);
        
        $count = $this->repository->countByStatus('pending');
        
        expect($count)->toBeGreaterThanOrEqual(3);
    }

    // ===== TESTES DE TRANSIÇÕES DE STATUS =====

    /** @test */
    public function it_can_mark_transaction_as_paid()
    {
        $transaction = FinancialTransaction::factory()
            ->for($this->project)
            ->create(['status' => 'pending', 'amount' => 100000]);
        
        $result = $this->repository->markAsPaid($transaction->id);
        
        expect($result)->toBeTrue();
        expect($transaction->fresh()->status)->toBe('paid');
    }

    /** @test */
    public function it_can_mark_transaction_as_pending()
    {
        $transaction = FinancialTransaction::factory()
            ->for($this->project)
            ->create(['status' => 'paid']);
        
        $result = $this->repository->markAsPending($transaction->id);
        
        expect($result)->toBeTrue();
        expect($transaction->fresh()->status)->toBe('pending');
    }

    /** @test */
    public function it_can_mark_transaction_as_cancelled()
    {
        $transaction = FinancialTransaction::factory()
            ->for($this->project)
            ->create(['status' => 'pending']);
        
        $result = $this->repository->markAsCancelled($transaction->id);
        
        expect($result)->toBeTrue();
        expect($transaction->fresh()->status)->toBe('cancelled');
    }

    // ===== TESTES DE BUSCA =====

    /** @test */
    public function it_can_search_transactions()
    {
        $transaction = FinancialTransaction::factory()
            ->for($this->project)
            ->create(['description' => 'Unique Description 12345']);
        
        $results = $this->repository->searchTransactions('Unique Description');
        
        expect($results->pluck('id')->contains($transaction->id))->toBeTrue();
    }

    // ===== TESTES DE ESTATÍSTICAS =====

    /** @test */
    public function it_can_get_statistics()
    {
        FinancialTransaction::factory(5)->for($this->project)->create();
        
        $stats = $this->repository->getStatistics();
        
        expect($stats)->toHaveKeys([
            'total_transactions',
            'total_amount',
            'pending_amount',
            'paid_amount',
            'average_amount',
        ]);
        expect($stats['total_transactions'])->toBeGreaterThanOrEqual(5);
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
    public function it_can_chain_query_methods()
    {
        FinancialTransaction::factory(3)->for($this->project)->create(['status' => 'pending']);
        
        $query = $this->repository->getQuery()
            ->where('status', 'pending')
            ->where('project_id', $this->project->id);
        
        expect($query->count())->toBeGreaterThanOrEqual(3);
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
        $transaction = FinancialTransaction::factory()
            ->for($this->project)
            ->create(['amount' => 999999999999]); // 9,999,999,999.99
        
        $found = $this->repository->findById($transaction->id);
        
        expect($found->amount)->toBe(999999999999);
    }

    /** @test */
    public function it_handles_zero_amount_correctly()
    {
        $transaction = FinancialTransaction::factory()
            ->for($this->project)
            ->create(['amount' => 0]);
        
        $found = $this->repository->findById($transaction->id);
        
        expect($found->amount)->toBe(0);
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $this->expectException(\Exception::class);
        
        $this->repository->create([
            'transaction_number' => 'FT-123',
            // Missing required fields
        ]);
    }

    /** @test */
    public function it_can_get_pending_transactions_for_allocation()
    {
        FinancialTransaction::factory(2)->for($this->project)->create([
            'type' => 'payable',
            'status' => 'pending',
        ]);
        FinancialTransaction::factory(1)->for($this->project)->create([
            'type' => 'payable',
            'status' => 'paid',
        ]);
        
        $pending = $this->repository->getPendingTransactionsForAllocation('payable');
        
        expect($pending->count())->toBeGreaterThanOrEqual(2);
        expect($pending->every(fn($t) => $t->status === 'pending'))->toBeTrue();
    }
}
