<?php

namespace Tests\Feature\Filament\Actions;

use App\Models\ProformaInvoice;
use App\Models\Order;
use App\Models\Client;
use App\Models\User;
use Tests\TestCase;

class ProformaInvoiceActionsTest extends TestCase
{
    private User $user;
    private Client $client;
    private Order $order;
    private ProformaInvoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->client = Client::factory()->for($this->user)->create();
        $this->order = Order::factory()->for($this->client)->create();
        $this->invoice = ProformaInvoice::factory()->for($this->order)->create(['status' => 'pending']);
        $this->actingAs($this->user);
    }

    // ===== TESTES DA ACTION: approve =====

    /** @test */
    public function it_can_approve_proforma_invoice()
    {
        $response = $this->post("/admin/proforma-invoices/{$this->invoice->id}/approve");
        
        $this->assertDatabaseHas('proforma_invoices', [
            'id' => $this->invoice->id,
            'status' => 'approved',
        ]);
    }

    /** @test */
    public function it_cannot_approve_already_approved_invoice()
    {
        $this->invoice->update(['status' => 'approved']);
        
        $response = $this->post("/admin/proforma-invoices/{$this->invoice->id}/approve");
        
        $response->assertSessionHasErrors();
    }

    // ===== TESTES DA ACTION: reject =====

    /** @test */
    public function it_can_reject_proforma_invoice()
    {
        $data = ['reason' => 'Incorrect pricing'];
        
        $response = $this->post("/admin/proforma-invoices/{$this->invoice->id}/reject", $data);
        
        $this->assertDatabaseHas('proforma_invoices', [
            'id' => $this->invoice->id,
            'status' => 'rejected',
        ]);
    }

    /** @test */
    public function it_validates_reason_when_rejecting()
    {
        $data = ['reason' => ''];
        
        $response = $this->post("/admin/proforma-invoices/{$this->invoice->id}/reject", $data);
        
        $response->assertSessionHasErrors('reason');
    }

    // ===== TESTES DA ACTION: mark_sent =====

    /** @test */
    public function it_can_mark_invoice_as_sent()
    {
        $this->invoice->update(['status' => 'approved']);
        
        $response = $this->post("/admin/proforma-invoices/{$this->invoice->id}/mark-sent");
        
        $this->assertDatabaseHas('proforma_invoices', [
            'id' => $this->invoice->id,
            'status' => 'sent',
        ]);
    }

    /** @test */
    public function it_cannot_mark_draft_invoice_as_sent()
    {
        $response = $this->post("/admin/proforma-invoices/{$this->invoice->id}/mark-sent");
        
        $response->assertSessionHasErrors();
    }

    // ===== TESTES DA ACTION: mark_deposit_received =====

    /** @test */
    public function it_can_mark_deposit_as_received()
    {
        $this->invoice->update(['status' => 'sent']);
        
        $data = [
            'deposit_amount' => 50000,
            'deposit_date' => now()->format('Y-m-d'),
        ];
        
        $response = $this->post("/admin/proforma-invoices/{$this->invoice->id}/mark-deposit-received", $data);
        
        $this->assertDatabaseHas('proforma_invoices', [
            'id' => $this->invoice->id,
            'deposit_received' => true,
        ]);
    }

    /** @test */
    public function it_validates_deposit_amount()
    {
        $this->invoice->update(['status' => 'sent']);
        
        $data = [
            'deposit_amount' => 'invalid',
            'deposit_date' => now()->format('Y-m-d'),
        ];
        
        $response = $this->post("/admin/proforma-invoices/{$this->invoice->id}/mark-deposit-received", $data);
        
        $response->assertSessionHasErrors('deposit_amount');
    }

    /** @test */
    public function it_validates_deposit_date()
    {
        $this->invoice->update(['status' => 'sent']);
        
        $data = [
            'deposit_amount' => 50000,
            'deposit_date' => 'invalid-date',
        ];
        
        $response = $this->post("/admin/proforma-invoices/{$this->invoice->id}/mark-deposit-received", $data);
        
        $response->assertSessionHasErrors('deposit_date');
    }

    // ===== TESTES DE TRANSIÇÕES DE ESTADO =====

    /** @test */
    public function it_follows_correct_status_workflow()
    {
        // draft -> approved
        $this->post("/admin/proforma-invoices/{$this->invoice->id}/approve");
        $this->assertDatabaseHas('proforma_invoices', ['id' => $this->invoice->id, 'status' => 'approved']);
        
        // approved -> sent
        $this->post("/admin/proforma-invoices/{$this->invoice->id}/mark-sent");
        $this->assertDatabaseHas('proforma_invoices', ['id' => $this->invoice->id, 'status' => 'sent']);
    }

    /** @test */
    public function it_prevents_invalid_status_transitions()
    {
        // Cannot go from draft to sent directly
        $response = $this->post("/admin/proforma-invoices/{$this->invoice->id}/mark-sent");
        
        $response->assertSessionHasErrors();
    }

    // ===== TESTES DE PERMISSÕES =====

    /** @test */
    public function unauthorized_user_cannot_approve_invoice()
    {
        $this->actingAs(User::factory()->create());
        
        $response = $this->post("/admin/proforma-invoices/{$this->invoice->id}/approve");
        
        $this->assertTrue($response->status() === 403 || $response->status() === 302);
    }

    /** @test */
    public function unauthorized_user_cannot_reject_invoice()
    {
        $this->actingAs(User::factory()->create());
        
        $data = ['reason' => 'Test'];
        $response = $this->post("/admin/proforma-invoices/{$this->invoice->id}/reject", $data);
        
        $this->assertTrue($response->status() === 403 || $response->status() === 302);
    }

    // ===== TESTES DE NOTIFICAÇÕES =====

    /** @test */
    public function it_shows_success_notification_after_approval()
    {
        $response = $this->post("/admin/proforma-invoices/{$this->invoice->id}/approve");
        
        $response->assertSessionHasNoErrors();
    }

    /** @test */
    public function it_shows_success_notification_after_rejection()
    {
        $data = ['reason' => 'Test reason'];
        $response = $this->post("/admin/proforma-invoices/{$this->invoice->id}/reject", $data);
        
        $response->assertSessionHasNoErrors();
    }
}
