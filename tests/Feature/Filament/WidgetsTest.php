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
use Livewire\Livewire;
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

    /** @dataProvider widgetProvider */
    public function test_widgets_use_mount_instead_of_construct(string $widgetClass)
    {
        $reflection = new \ReflectionClass($widgetClass);
        $this->assertFalse($reflection->hasMethod("__construct"), "Widget {$widgetClass} should not have a __construct method.");
        $this->assertTrue($reflection->hasMethod("mount"), "Widget {$widgetClass} should have a mount method.");
    }

    public static function widgetProvider(): array
    {
        return [
            [CalendarWidget::class],
            [FinancialOverviewWidget::class],
            [ProjectExpensesWidget::class],
            [PurchaseOrderStatsWidget::class],
            [RelatedDocumentsWidget::class],
            [RfqStatsWidget::class],
        ];
    }
}
