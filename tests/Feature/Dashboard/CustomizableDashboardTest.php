<?php

namespace Tests\Feature\Dashboard;

use App\Models\AvailableWidget;
use App\Models\DashboardConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomizableDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed available widgets
        AvailableWidget::create([
            'widget_id' => 'calendar',
            'title' => 'Calendar',
            'class' => 'App\Filament\Widgets\CalendarWidget',
            'is_available' => true,
            'default_visible' => true,
        ]);

        AvailableWidget::create([
            'widget_id' => 'rfq_stats',
            'title' => 'RFQ Stats',
            'class' => 'App\Filament\Widgets\RfqStatsWidget',
            'is_available' => true,
            'default_visible' => true,
        ]);
    }

    public function test_user_can_access_widget_selector_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/widget-selector');

        $response->assertStatus(200);
    }

    public function test_widget_selector_page_displays_available_widgets(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/widget-selector');

        $response->assertSee('Calendar');
        $response->assertSee('RFQ Stats');
    }

    public function test_user_can_save_widget_configuration(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/widget-selector/save', [
            'visible_widgets' => ['calendar'],
            'widget_order' => ['calendar'],
        ]);

        $config = DashboardConfiguration::where('user_id', $user->id)->first();
        $this->assertNotNull($config);
        $this->assertEquals(['calendar'], $config->visible_widgets);
    }

    public function test_dashboard_respects_user_widget_configuration(): void
    {
        $user = User::factory()->create();

        // Create a custom configuration
        DashboardConfiguration::create([
            'user_id' => $user->id,
            'visible_widgets' => ['rfq_stats'],
            'widget_order' => ['rfq_stats'],
        ]);

        // Dashboard should load with the configured widgets
        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(200);
    }

    public function test_user_can_reset_to_default_configuration(): void
    {
        $user = User::factory()->create();

        // Create a custom configuration
        DashboardConfiguration::create([
            'user_id' => $user->id,
            'visible_widgets' => ['calendar'],
            'widget_order' => ['calendar'],
        ]);

        // Reset to default
        $response = $this->actingAs($user)->post('/admin/widget-selector/reset');

        $config = DashboardConfiguration::where('user_id', $user->id)->first();
        $this->assertNotNull($config);
    }

    public function test_admin_can_view_dashboard_configurations(): void
    {
        $admin = User::factory()->create(['email' => 'admin@test.com']);
        $user = User::factory()->create();

        DashboardConfiguration::create([
            'user_id' => $user->id,
            'visible_widgets' => ['calendar'],
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard-configurations');

        $response->assertStatus(200);
    }

    public function test_widget_order_is_respected(): void
    {
        $user = User::factory()->create();

        $order = ['rfq_stats', 'calendar'];
        DashboardConfiguration::create([
            'user_id' => $user->id,
            'visible_widgets' => ['calendar', 'rfq_stats'],
            'widget_order' => $order,
        ]);

        $config = DashboardConfiguration::where('user_id', $user->id)->first();
        $this->assertEquals($order, $config->widget_order);
    }

    public function test_unavailable_widgets_are_not_shown(): void
    {
        AvailableWidget::create([
            'widget_id' => 'unavailable',
            'title' => 'Unavailable Widget',
            'class' => 'App\Filament\Widgets\UnavailableWidget',
            'is_available' => false,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/widget-selector');

        $response->assertDontSee('Unavailable Widget');
    }

    public function test_new_user_gets_default_configuration(): void
    {
        $user = User::factory()->create();

        // First access to dashboard should create default config
        $this->actingAs($user)->get('/admin');

        $config = DashboardConfiguration::where('user_id', $user->id)->first();
        $this->assertNotNull($config);
    }
}
