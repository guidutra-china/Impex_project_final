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

    public function test_register_widgets(): void
    {
        $this->service->registerWidgets();

        $widget = AvailableWidget::where('widget_id', 'calendar')->first();
        $this->assertNotNull($widget);
        $this->assertEquals('App\Filament\Widgets\CalendarWidget', $widget->class);
        $this->assertEquals('Calendário', $widget->title);
    }

    public function test_get_available_widgets_for_user(): void
    {
        AvailableWidget::create([
            'widget_id' => 'calendar',
            'title' => 'Calendar',
            'class' => 'App\Filament\Widgets\CalendarWidget',
            'is_available' => true,
            'requires_permission' => null,
        ]);

        AvailableWidget::create([
            'widget_id' => 'unavailable',
            'title' => 'Unavailable',
            'class' => 'App\Filament\Widgets\UnavailableWidget',
            'is_available' => false,
            'requires_permission' => null,
        ]);

        $user = User::factory()->create();
        $widgets = $this->service->getAvailableWidgetsForUser($user);

        $this->assertCount(1, $widgets);
        $this->assertEquals('calendar', $widgets[0]['id']);
    }

    public function test_get_widget(): void
    {
        AvailableWidget::create([
            'widget_id' => 'calendar',
            'title' => 'Calendar',
            'class' => 'App\Filament\Widgets\CalendarWidget',
            'is_available' => true,
            'requires_permission' => null,
        ]);

        $widget = $this->service->getWidget('calendar');

        $this->assertNotNull($widget);
        $this->assertEquals('calendar', $widget['id']);
    }

    public function test_get_widget_returns_null_if_not_found(): void
    {
        $widget = $this->service->getWidget('non_existent');

        $this->assertNull($widget);
    }

    public function test_register_widgets_creates_records(): void
    {
        $this->service->registerWidgets();

        $widgets = AvailableWidget::all();
        $this->assertGreaterThan(0, $widgets->count());
    }

    public function test_widget_exists(): void
    {
        AvailableWidget::create([
            'widget_id' => 'calendar',
            'title' => 'Calendar',
            'class' => 'App\Filament\Widgets\CalendarWidget',
            'is_available' => true,
            'requires_permission' => null,
        ]);

        $this->assertTrue($this->service->widgetExists('calendar'));
        $this->assertFalse($this->service->widgetExists('non_existent'));
    }
}
