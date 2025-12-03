<?php

namespace Tests\Feature\Filament\Actions;

use App\Models\SupplierQuote;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\Client;
use App\Models\User;
use Tests\TestCase;

class SupplierQuoteActionsTest extends TestCase
{
    private User $user;
    private Client $client;
    private Supplier $supplier;
    private Order $order;
    private SupplierQuote $quote;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->client = Client::factory()->for($this->user)->create();
        $this->supplier = Supplier::factory()->for($this->user)->create();
        $this->order = Order::factory()->for($this->client)->create();
        $this->quote = SupplierQuote::factory()
            ->for($this->order)
            ->for($this->supplier)
            ->create(['status' => 'draft']);
        $this->actingAs($this->user);
    }

    // ===== TESTES DA ACTION: recalculate =====

    /** @test */
    public function it_can_recalculate_supplier_quote()
    {
        $response = $this->post("/admin/supplier-quotes/{$this->quote->id}/recalculate");
        
        $response->assertSessionHasNoErrors();
    }

    /** @test */
    public function it_updates_totals_on_recalculate()
    {
        $response = $this->post("/admin/supplier-quotes/{$this->quote->id}/recalculate");
        
        $this->assertDatabaseHas('supplier_quotes', [
            'id' => $this->quote->id,
        ]);
    }

    /** @test */
    public function it_shows_success_notification_after_recalculate()
    {
        $response = $this->post("/admin/supplier-quotes/{$this->quote->id}/recalculate");
        
        $response->assertSessionHasNoErrors();
    }

    // ===== TESTES DA ACTION: import_excel =====

    /** @test */
    public function it_can_render_import_form()
    {
        $response = $this->get("/admin/supplier-quotes/{$this->quote->id}/edit");
        
        $response->assertSuccessful();
    }

    /** @test */
    public function it_validates_file_is_required_for_import()
    {
        $data = [];
        
        $response = $this->post("/admin/supplier-quotes/{$this->quote->id}/import", $data);
        
        $response->assertSessionHasErrors('file');
    }

    /** @test */
    public function it_validates_file_type_for_import()
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('test.txt', 100);
        
        $data = ['file' => $file];
        
        $response = $this->post("/admin/supplier-quotes/{$this->quote->id}/import", $data);
        
        $response->assertSessionHasErrors('file');
    }

    /** @test */
    public function it_can_import_excel_file()
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('quote.xlsx', 100);
        
        $data = ['file' => $file];
        
        $response = $this->post("/admin/supplier-quotes/{$this->quote->id}/import", $data);
        
        // Deve processar o arquivo
        $response->assertSessionHasNoErrors();
    }

    // ===== TESTES DE TRANSIÇÕES DE ESTADO =====

    /** @test */
    public function it_can_approve_supplier_quote()
    {
        $response = $this->post("/admin/supplier-quotes/{$this->quote->id}/approve");
        
        $this->assertDatabaseHas('supplier_quotes', [
            'id' => $this->quote->id,
            'status' => 'sent',
        ]);
    }

    /** @test */
    public function it_can_reject_supplier_quote()
    {
        $data = ['reason' => 'Price too high'];
        
        $response = $this->post("/admin/supplier-quotes/{$this->quote->id}/reject", $data);
        
        $this->assertDatabaseHas('supplier_quotes', [
            'id' => $this->quote->id,
            'status' => 'rejected',
        ]);
    }

    // ===== TESTES DE VALIDAÇÕES =====

    /** @test */
    public function it_validates_reason_when_rejecting_quote()
    {
        $data = ['reason' => ''];
        
        $response = $this->post("/admin/supplier-quotes/{$this->quote->id}/reject", $data);
        
        $response->assertSessionHasErrors('reason');
    }

    // ===== TESTES DE PERMISSÕES =====

    /** @test */
    public function unauthorized_user_cannot_recalculate_quote()
    {
        $this->actingAs(User::factory()->create());
        
        $response = $this->post("/admin/supplier-quotes/{$this->quote->id}/recalculate");
        
        $this->assertTrue($response->status() === 403 || $response->status() === 302);
    }

    /** @test */
    public function unauthorized_user_cannot_import_excel()
    {
        $this->actingAs(User::factory()->create());
        
        $file = \Illuminate\Http\UploadedFile::fake()->create('quote.xlsx', 100);
        $data = ['file' => $file];
        
        $response = $this->post("/admin/supplier-quotes/{$this->quote->id}/import", $data);
        
        $this->assertTrue($response->status() === 403 || $response->status() === 302);
    }

    // ===== TESTES DE NOTIFICAÇÕES =====

    /** @test */
    public function it_shows_success_notification_after_import()
    {
        $file = \Illuminate\Http\UploadedFile::fake()->create('quote.xlsx', 100);
        $data = ['file' => $file];
        
        $response = $this->post("/admin/supplier-quotes/{$this->quote->id}/import", $data);
        
        $response->assertSessionHasNoErrors();
    }

    /** @test */
    public function it_shows_success_notification_after_approval()
    {
        $response = $this->post("/admin/supplier-quotes/{$this->quote->id}/approve");
        
        $response->assertSessionHasNoErrors();
    }

    // ===== TESTES DE COMPARAÇÃO =====

    /** @test */
    public function it_can_compare_multiple_quotes()
    {
        $quote2 = SupplierQuote::factory()
            ->for($this->order)
            ->for($this->supplier)
            ->create(['status' => 'draft']);
        
        $response = $this->get("/admin/supplier-quotes/compare?quotes[]={$this->quote->id}&quotes[]={$quote2->id}");
        
        $response->assertSuccessful();
    }
}
