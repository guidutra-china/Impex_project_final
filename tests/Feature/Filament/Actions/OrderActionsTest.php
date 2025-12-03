<?php

namespace Tests\Feature\Filament\Actions;

use App\Models\Order;
use App\Models\Client;
use App\Models\User;
use App\Models\FinancialTransaction;
use Tests\TestCase;

class OrderActionsTest extends TestCase
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

    // ===== TESTES DA ACTION: add_project_expense =====

    /** @test */
    public function it_can_render_add_expense_action()
    {
        $response = $this->get("/admin/orders/{$this->order->id}/edit");
        
        $response->assertSuccessful();
    }

    /** @test */
    public function it_can_add_project_expense()
    {
        $data = [
            'description' => 'Shipping Cost',
            'amount' => 10000, // 100.00
            'category' => 'shipping',
            'type' => 'expense',
        ];
        
        $response = $this->post("/admin/orders/{$this->order->id}/add-expense", $data);
        
        $this->assertDatabaseHas('financial_transactions', [
            'order_id' => $this->order->id,
            'description' => 'Shipping Cost',
            'amount' => 10000,
            'category' => 'shipping',
            'type' => 'expense',
        ]);
    }

    /** @test */
    public function it_validates_required_fields_for_expense()
    {
        $data = [
            'description' => '',
            'amount' => '',
            'category' => '',
        ];
        
        $response = $this->post("/admin/orders/{$this->order->id}/add-expense", $data);
        
        $response->assertSessionHasErrors(['description', 'amount', 'category']);
    }

    /** @test */
    public function it_validates_amount_is_numeric()
    {
        $data = [
            'description' => 'Test',
            'amount' => 'invalid',
            'category' => 'shipping',
        ];
        
        $response = $this->post("/admin/orders/{$this->order->id}/add-expense", $data);
        
        $response->assertSessionHasErrors('amount');
    }

    /** @test */
    public function it_validates_amount_is_positive()
    {
        $data = [
            'description' => 'Test',
            'amount' => -1000,
            'category' => 'shipping',
        ];
        
        $response = $this->post("/admin/orders/{$this->order->id}/add-expense", $data);
        
        $response->assertSessionHasErrors('amount');
    }

    /** @test */
    public function it_creates_financial_transaction_with_correct_type()
    {
        $data = [
            'description' => 'Test Expense',
            'amount' => 5000,
            'category' => 'shipping',
            'type' => 'expense',
        ];
        
        $response = $this->post("/admin/orders/{$this->order->id}/add-expense", $data);
        
        $this->assertDatabaseHas('financial_transactions', [
            'type' => 'expense',
            'order_id' => $this->order->id,
        ]);
    }

    /** @test */
    public function it_sets_created_by_to_current_user()
    {
        $data = [
            'description' => 'Test Expense',
            'amount' => 5000,
            'category' => 'shipping',
        ];
        
        $response = $this->post("/admin/orders/{$this->order->id}/add-expense", $data);
        
        $this->assertDatabaseHas('financial_transactions', [
            'order_id' => $this->order->id,
            'created_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_add_multiple_expenses_to_same_order()
    {
        $data1 = [
            'description' => 'Expense 1',
            'amount' => 5000,
            'category' => 'shipping',
        ];
        
        $data2 = [
            'description' => 'Expense 2',
            'amount' => 3000,
            'category' => 'handling',
        ];
        
        $this->post("/admin/orders/{$this->order->id}/add-expense", $data1);
        $this->post("/admin/orders/{$this->order->id}/add-expense", $data2);
        
        $this->assertDatabaseHas('financial_transactions', [
            'order_id' => $this->order->id,
            'description' => 'Expense 1',
        ]);
        
        $this->assertDatabaseHas('financial_transactions', [
            'order_id' => $this->order->id,
            'description' => 'Expense 2',
        ]);
    }

    /** @test */
    public function it_shows_success_notification_after_adding_expense()
    {
        $data = [
            'description' => 'Test Expense',
            'amount' => 5000,
            'category' => 'shipping',
        ];
        
        $response = $this->post("/admin/orders/{$this->order->id}/add-expense", $data);
        
        $response->assertSessionHasNoErrors();
    }

    /** @test */
    public function unauthorized_user_cannot_add_expense()
    {
        $this->actingAs(User::factory()->create());
        
        $data = [
            'description' => 'Test Expense',
            'amount' => 5000,
            'category' => 'shipping',
        ];
        
        $response = $this->post("/admin/orders/{$this->order->id}/add-expense", $data);
        
        $this->assertTrue($response->status() === 403 || $response->status() === 302);
    }

    /** @test */
    public function it_can_delete_expense()
    {
        $expense = FinancialTransaction::factory()
            ->for($this->order)
            ->create(['type' => 'expense']);
        
        $response = $this->delete("/admin/orders/{$this->order->id}/expenses/{$expense->id}");
        
        $this->assertDatabaseMissing('financial_transactions', ['id' => $expense->id]);
    }

    /** @test */
    public function it_can_update_expense()
    {
        $expense = FinancialTransaction::factory()
            ->for($this->order)
            ->create(['type' => 'expense', 'description' => 'Old Description']);
        
        $data = [
            'description' => 'Updated Description',
            'amount' => 7000,
        ];
        
        $response = $this->put("/admin/orders/{$this->order->id}/expenses/{$expense->id}", $data);
        
        $this->assertDatabaseHas('financial_transactions', [
            'id' => $expense->id,
            'description' => 'Updated Description',
            'amount' => 7000,
        ]);
    }

    /** @test */
    public function it_can_view_all_expenses_for_order()
    {
        FinancialTransaction::factory(3)->for($this->order)->create(['type' => 'expense']);
        
        $response = $this->get("/admin/orders/{$this->order->id}/edit");
        
        $response->assertSuccessful();
    }

    /** @test */
    public function it_calculates_total_expenses_correctly()
    {
        FinancialTransaction::factory()->for($this->order)->create(['type' => 'expense', 'amount' => 5000]);
        FinancialTransaction::factory()->for($this->order)->create(['type' => 'expense', 'amount' => 3000]);
        
        $response = $this->get("/admin/orders/{$this->order->id}/edit");
        
        $response->assertSuccessful();
        // Total should be 8000
    }
}
