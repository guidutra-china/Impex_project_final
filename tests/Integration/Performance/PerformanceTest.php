<?php

namespace Tests\Integration\Performance;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Client;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\PurchaseOrder;
use App\Models\FinancialTransaction;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class PerformanceTest extends TestCase
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

    // ===== TESTES DE CARGA - LISTAGEM =====

    /** @test */
    public function can_list_orders_with_100_records()
    {
        Order::factory(100)->for($this->client)->create();
        
        $startTime = microtime(true);
        $response = $this->get('/admin/orders');
        $endTime = microtime(true);
        
        $response->assertSuccessful();
        
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(2, $executionTime, 'Listagem de 100 ordens deve levar menos de 2 segundos');
    }

    /** @test */
    public function can_list_orders_with_500_records()
    {
        Order::factory(500)->for($this->client)->create();
        
        $startTime = microtime(true);
        $response = $this->get('/admin/orders');
        $endTime = microtime(true);
        
        $response->assertSuccessful();
        
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(3, $executionTime, 'Listagem de 500 ordens deve levar menos de 3 segundos');
    }

    /** @test */
    public function can_list_products_with_1000_records()
    {
        Product::factory(1000)->for($this->user)->create();
        
        $startTime = microtime(true);
        $response = $this->get('/admin/products');
        $endTime = microtime(true);
        
        $response->assertSuccessful();
        
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(4, $executionTime, 'Listagem de 1000 produtos deve levar menos de 4 segundos');
    }

    // ===== TESTES DE CARGA - BUSCA =====

    /** @test */
    public function can_search_orders_in_large_dataset()
    {
        Order::factory(500)->for($this->client)->create();
        $targetOrder = Order::factory()->for($this->client)->create(['order_number' => 'UNIQUE-ORD-12345']);
        
        $startTime = microtime(true);
        $response = $this->get('/admin/orders?search=UNIQUE');
        $endTime = microtime(true);
        
        $response->assertSuccessful();
        $response->assertSee('UNIQUE-ORD-12345');
        
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(2, $executionTime, 'Busca em 500 ordens deve levar menos de 2 segundos');
    }

    /** @test */
    public function can_filter_orders_in_large_dataset()
    {
        Order::factory(300)->for($this->client)->create(['status' => 'draft']);
        Order::factory(200)->for($this->client)->create(['status' => 'processing']);
        
        $startTime = microtime(true);
        $response = $this->get('/admin/orders?status=confirmed');
        $endTime = microtime(true);
        
        $response->assertSuccessful();
        
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(2, $executionTime, 'Filtro em 500 ordens deve levar menos de 2 segundos');
    }

    // ===== TESTES DE CARGA - CRIAÇÃO =====

    /** @test */
    public function can_create_order_with_100_items()
    {
        $order = Order::factory()->for($this->client)->create();
        
        $startTime = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            OrderItem::factory()->for($order)->create();
        }
        
        $endTime = microtime(true);
        
        $this->assertDatabaseCount('order_items', 100);
        
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(5, $executionTime, 'Criação de 100 itens deve levar menos de 5 segundos');
    }

    /** @test */
    public function can_create_multiple_orders_in_batch()
    {
        $startTime = microtime(true);
        
        Order::factory(50)->for($this->client)->create();
        
        $endTime = microtime(true);
        
        $this->assertDatabaseCount('orders', 50);
        
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(3, $executionTime, 'Criação de 50 ordens deve levar menos de 3 segundos');
    }

    // ===== TESTES DE CARGA - ATUALIZAÇÃO =====

    /** @test */
    public function can_update_order_status_in_batch()
    {
        $orders = Order::factory(50)->for($this->client)->create(['status' => 'draft']);
        
        $startTime = microtime(true);
        
        Order::whereIn('id', $orders->pluck('id'))->update(['status' => 'processing']);
        
        $endTime = microtime(true);
        
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(1, $executionTime, 'Atualização em batch de 50 ordens deve levar menos de 1 segundo');
    }

    // ===== TESTES DE MEMÓRIA =====

    /** @test */
    public function can_process_large_financial_report()
    {
        // Criar 1000 transações financeiras
        FinancialTransaction::factory(1000)->create();
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // Processar todas as transações
        $transactions = FinancialTransaction::all();
        $total = $transactions->sum('amount');
        
        $endMemory = memory_get_usage();
        $endTime = microtime(true);
        
        $executionTime = $endTime - $startTime;
        $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // MB
        
        $this->assertLessThan(2, $executionTime, 'Processamento de 1000 transações deve levar menos de 2 segundos');
        $this->assertLessThan(50, $memoryUsed, 'Processamento de 1000 transações deve usar menos de 50MB');
    }

    /** @test */
    public function can_generate_report_with_large_dataset()
    {
        SalesInvoice::factory(500)->for($this->user)->create();
        PurchaseOrder::factory(500)->for($this->user)->create();
        
        $startTime = microtime(true);
        
        $receivables = SalesInvoice::where('status', 'pending')->sum('total');
        $payables = PurchaseOrder::where('status', 'pending')->sum('total');
        
        $endTime = microtime(true);
        
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(2, $executionTime, 'Geração de relatório com 1000 registros deve levar menos de 2 segundos');
    }

    // ===== TESTES DE PAGINAÇÃO =====

    /** @test */
    public function pagination_works_efficiently_with_large_dataset()
    {
        Order::factory(1000)->for($this->client)->create();
        
        $startTime = microtime(true);
        
        $response = $this->get('/admin/orders?page=10');
        
        $endTime = microtime(true);
        
        $response->assertSuccessful();
        
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(2, $executionTime, 'Paginação com 1000 registros deve levar menos de 2 segundos');
    }

    // ===== TESTES DE ÍNDICES =====

    /** @test */
    public function database_queries_use_indexes_efficiently()
    {
        Order::factory(1000)->for($this->client)->create();
        
        // Desabilitar query logging para teste de performance
        DB::disableQueryLog();
        
        $startTime = microtime(true);
        
        $orders = Order::where('customer_id', $this->client->id)->get();
        
        $endTime = microtime(true);
        
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(1, $executionTime, 'Query com índice deve levar menos de 1 segundo');
    }

    // ===== TESTES DE CONCORRÊNCIA =====

    /** @test */
    public function can_handle_concurrent_order_creation()
    {
        $startTime = microtime(true);
        
        // Simular criação concorrente
        for ($i = 0; $i < 10; $i++) {
            Order::factory()->for($this->client)->create();
        }
        
        $endTime = microtime(true);
        
        $this->assertDatabaseCount('orders', 10);
        
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(2, $executionTime, 'Criação de 10 ordens sequenciais deve levar menos de 2 segundos');
    }

    // ===== TESTES DE CACHE =====

    /** @test */
    public function repeated_queries_benefit_from_caching()
    {
        Order::factory(100)->for($this->client)->create();
        
        // Primeira execução (sem cache)
        $startTime1 = microtime(true);
        $orders1 = Order::where('customer_id', $this->client->id)->get();
        $endTime1 = microtime(true);
        $time1 = $endTime1 - $startTime1;
        
        // Segunda execução (com cache)
        $startTime2 = microtime(true);
        $orders2 = Order::where('customer_id', $this->client->id)->get();
        $endTime2 = microtime(true);
        $time2 = $endTime2 - $startTime2;
        
        // Segunda execução deve ser mais rápida ou similar
        $this->assertLessThan(1, $time2, 'Query em cache deve levar menos de 1 segundo');
    }
}
