<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\WidgetSelectorPage;
use App\Models\AvailableWidget;
use App\Models\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WidgetSelectorPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Seed available widgets
        AvailableWidget::factory()->create(["widget_id" => "calendar", "title" => "Calendar"]);
        AvailableWidget::factory()->create(["widget_id" => "rfq_stats", "title" => "RFQ Stats"]);
    }

    public function test_can_render_page()
    {
        Livewire::test(WidgetSelectorPage::class)
            ->assertSuccessful();
    }

    public function test_view_is_not_static()
    {
        $page = new WidgetSelectorPage();
        $reflection = new \ReflectionClass($page);
        $viewProperty = $reflection->getProperty("view");

        $this->assertFalse($viewProperty->isStatic());
    }

    public function test_can_save_configuration()
    {
        Livewire::test(WidgetSelectorPage::class)
            ->set("selectedWidgets", ["calendar"])
            ->call("saveConfiguration");

        $this->assertDatabaseHas("dashboard_configurations", [
            "user_id" => $this->user->id,
            "visible_widgets" => json_encode(["calendar"]),
        ]);
    }

    public function test_can_reset_to_default()
    {
        Livewire::test(WidgetSelectorPage::class)
            ->set("selectedWidgets", ["calendar"])
            ->call("saveConfiguration")
            ->call("resetToDefault");

        $this->assertDatabaseHas("dashboard_configurations", [
            "user_id" => $this->user->id,
            "visible_widgets" => json_encode(["calendar", "rfq_stats"]),
        ]);
    }
}
