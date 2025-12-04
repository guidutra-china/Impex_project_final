<?php

namespace Tests\Feature\Filament;

use App\Filament\Widgets\CalendarWidget;
use App\Filament\Widgets\FinancialOverviewWidget;
use App\Filament\Widgets\ProjectExpensesWidget;
use App\Filament\Widgets\PurchaseOrderStatsWidget;
use App\Filament\Widgets\RelatedDocumentsWidget;
use App\Filament\Widgets\RfqStatsWidget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WidgetsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_calendar_widget_uses_mount()
    {
        $reflection = new \ReflectionClass(CalendarWidget::class);
        $this->assertFalse($reflection->hasMethod("__construct"), "CalendarWidget should not have a __construct method.");
        $this->assertTrue($reflection->hasMethod("mount"), "CalendarWidget should have a mount method.");
    }

    public function test_financial_overview_widget_uses_mount()
    {
        $reflection = new \ReflectionClass(FinancialOverviewWidget::class);
        $this->assertFalse($reflection->hasMethod("__construct"), "FinancialOverviewWidget should not have a __construct method.");
        $this->assertTrue($reflection->hasMethod("mount"), "FinancialOverviewWidget should have a mount method.");
    }

    public function test_project_expenses_widget_uses_mount()
    {
        $reflection = new \ReflectionClass(ProjectExpensesWidget::class);
        $this->assertFalse($reflection->hasMethod("__construct"), "ProjectExpensesWidget should not have a __construct method.");
        $this->assertTrue($reflection->hasMethod("mount"), "ProjectExpensesWidget should have a mount method.");
    }

    public function test_purchase_order_stats_widget_uses_mount()
    {
        $reflection = new \ReflectionClass(PurchaseOrderStatsWidget::class);
        $this->assertFalse($reflection->hasMethod("__construct"), "PurchaseOrderStatsWidget should not have a __construct method.");
        $this->assertTrue($reflection->hasMethod("mount"), "PurchaseOrderStatsWidget should have a mount method.");
    }

    public function test_related_documents_widget_uses_mount()
    {
        $reflection = new \ReflectionClass(RelatedDocumentsWidget::class);
        $this->assertFalse($reflection->hasMethod("__construct"), "RelatedDocumentsWidget should not have a __construct method.");
        $this->assertTrue($reflection->hasMethod("mount"), "RelatedDocumentsWidget should have a mount method.");
    }

    public function test_rfq_stats_widget_uses_mount()
    {
        $reflection = new \ReflectionClass(RfqStatsWidget::class);
        $this->assertFalse($reflection->hasMethod("__construct"), "RfqStatsWidget should not have a __construct method.");
        $this->assertTrue($reflection->hasMethod("mount"), "RfqStatsWidget should have a mount method.");
    }
}
