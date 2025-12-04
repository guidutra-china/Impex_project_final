<?php

namespace Tests\Unit\Services;

use App\Models\AvailableWidget;
use App\Models\User;
use App\Services\WidgetRegistryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WidgetRegistryServiceTest extends TestCase
{
    use RefreshDatabase;

    private WidgetRegistryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WidgetRegistryService::class);
    }

    public function test_register_widget(): void
    {
        $widgetId = 'test_widget';
        $class = 'App\Filament\Widgets\TestWidget';
        $metadata = [
            'title' => 'Test Widget',
            'description' => 'A test widget',
            'icon' => 'heroicon-o-chart-bar',
        ];

        $this->service->registerWidget($widgetId, $class, $metadata);

        $widget = AvailableWidget::where('widget_id', $widgetId)->first();
        $this->assertNotNull($widget);
        $this->assertEquals($class, $widget->class);
        $this->assertEquals('Test Widget', $widget->title);
    }

    public function test_get_available_widgets(): void
    {
        AvailableWidget::create([
            'widget_id' => 'calendar',
            'title' => 'Calendar',
            'class' => 'App\Filament\Widgets\CalendarWidget',
            'is_available' => true,
        ]);

        AvailableWidget::create([
            'widget_id' => 'unavailable',
            'title' => 'Unavailable',
            'class' => 'App\Filament\Widgets\UnavailableWidget',
            'is_available' => false,
        ]);

        $user = User::factory()->create();
        $widgets = $this->service->getAvailableWidgets($user);

        $this->assertCount(1, $widgets);
        $this->assertEquals('calendar', $widgets[0]->widget_id);
    }

    public function test_get_widget_by_id(): void
    {
        AvailableWidget::create([
            'widget_id' => 'calendar',
            'title' => 'Calendar',
            'class' => 'App\Filament\Widgets\CalendarWidget',
        ]);

        $widget = $this->service->getWidgetById('calendar');

        $this->assertNotNull($widget);
        $this->assertEquals('calendar', $widget->widget_id);
    }

    public function test_get_widget_by_id_returns_null_if_not_found(): void
    {
        $widget = $this->service->getWidgetById('non_existent');

        $this->assertNull($widget);
    }

    public function test_seed_default_widgets(): void
    {
        $this->service->seedDefaultWidgets();

        $widgets = AvailableWidget::all();
        $this->assertGreaterThan(0, $widgets->count());
    }
}
