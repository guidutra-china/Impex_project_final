<?php

namespace Tests\Unit\Models;

use App\Models\AvailableWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailableWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_available_widget_can_be_created(): void
    {
        $widget = AvailableWidget::create([
            'widget_id' => 'calendar',
            'title' => 'Calendar',
            'class' => 'App\Filament\Widgets\CalendarWidget',
            'icon' => 'heroicon-o-calendar',
            'is_available' => true,
        ]);

        $this->assertNotNull($widget->id);
        $this->assertEquals('calendar', $widget->widget_id);
        $this->assertEquals('Calendar', $widget->title);
    }

    public function test_widget_id_is_unique(): void
    {
        AvailableWidget::create([
            'widget_id' => 'calendar',
            'title' => 'Calendar',
            'class' => 'App\Filament\Widgets\CalendarWidget',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        AvailableWidget::create([
            'widget_id' => 'calendar',
            'title' => 'Another Calendar',
            'class' => 'App\Filament\Widgets\AnotherCalendarWidget',
        ]);
    }

    public function test_available_widget_has_default_values(): void
    {
        $widget = AvailableWidget::create([
            'widget_id' => 'test',
            'title' => 'Test Widget',
            'class' => 'App\Filament\Widgets\TestWidget',
            'is_available' => true,
            'default_visible' => false,
            'category' => 'general',
        ]);

        $this->assertTrue($widget->is_available);
        $this->assertFalse($widget->default_visible);
        $this->assertEquals('general', $widget->category);
    }

    public function test_available_widget_get_available(): void
    {
        AvailableWidget::create([
            'widget_id' => 'available',
            'title' => 'Available',
            'class' => 'App\Filament\Widgets\AvailableWidget',
            'is_available' => true,
        ]);

        AvailableWidget::create([
            'widget_id' => 'unavailable',
            'title' => 'Unavailable',
            'class' => 'App\Filament\Widgets\UnavailableWidget',
            'is_available' => false,
        ]);

        $available = AvailableWidget::getAvailable();

        $this->assertCount(1, $available);
        $this->assertContains('available', $available);
    }
}
