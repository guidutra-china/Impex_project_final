<?php

namespace Tests\Feature\Dashboard;

use App\Models\AvailableWidget;
use App\Models\DashboardConfiguration;
use App\Models\User;
use App\Services\DashboardConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomizableDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected DashboardConfigurationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DashboardConfigurationService::class);

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

    public function test_dashboard_configuration_service_integration(): void
    {
        $user = User::factory()->create();

        $config = $this->service->getOrCreateConfiguration($user);

        $this->assertNotNull($config);
        $this->assertEquals($user->id, $config->user_id);
    }

    public function test_available_widgets_are_seeded(): void
    {
        $this->assertGreaterThan(0, AvailableWidget::count());
    }

    public function test_user_can_add_widget(): void
    {
        $user = User::factory()->create();

        $this->service->getOrCreateConfiguration($user);
        $this->service->addWidget($user, 'calendar');

        $config = DashboardConfiguration::where('user_id', $user->id)->first();
        $this->assertNotNull($config);
        $this->assertContains('calendar', $config->visible_widgets);
    }

    public function test_user_can_remove_widget(): void
    {
        $user = User::factory()->create();

        $this->service->getOrCreateConfiguration($user);
        $this->service->addWidget($user, 'calendar');
        $this->service->removeWidget($user, 'calendar');

        $config = DashboardConfiguration::where('user_id', $user->id)->first();
        $this->assertNotContains('calendar', $config->visible_widgets);
    }

    public function test_user_can_update_widget_order(): void
    {
        $user = User::factory()->create();

        $this->service->getOrCreateConfiguration($user);
        $this->service->updateWidgetOrder($user, ['rfq_stats', 'calendar']);

        $config = DashboardConfiguration::where('user_id', $user->id)->first();
        $this->assertEquals(['rfq_stats', 'calendar'], $config->widget_order);
    }

    public function test_user_can_reset_to_default_configuration(): void
    {
        $user = User::factory()->create();

        $this->service->getOrCreateConfiguration($user);
        $this->service->addWidget($user, 'calendar');
        $this->service->resetToDefault($user);

        $config = DashboardConfiguration::where('user_id', $user->id)->first();
        $this->assertGreaterThan(0, count($config->visible_widgets));
    }

    public function test_multiple_users_have_separate_configurations(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $config1 = $this->service->getOrCreateConfiguration($user1);
        $config2 = $this->service->getOrCreateConfiguration($user2);

        $this->assertNotEquals($config1->id, $config2->id);
        $this->assertEquals($user1->id, $config1->user_id);
        $this->assertEquals($user2->id, $config2->user_id);
    }

    public function test_widget_settings_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->service->getOrCreateConfiguration($user);
        $this->service->updateWidgetSettings($user, 'calendar', ['show_weekends' => false]);

        $config = DashboardConfiguration::where('user_id', $user->id)->first();
        $this->assertIsArray($config->widget_settings);
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

    public function test_unavailable_widgets_are_not_returned(): void
    {
        AvailableWidget::create([
            'widget_id' => 'unavailable',
            'title' => 'Unavailable Widget',
            'class' => 'App\Filament\Widgets\UnavailableWidget',
            'is_available' => false,
        ]);

        $user = User::factory()->create();

        $availableWidgets = $this->service->getAllAvailableWidgets($user);

        $widgetIds = array_column($availableWidgets, 'id');
        $this->assertNotContains('unavailable', $widgetIds);
    }

    public function test_new_user_gets_default_configuration_on_first_access(): void
    {
        $user = User::factory()->create();

        // First access should create default config
        $config = $this->service->getOrCreateConfiguration($user);

        $this->assertNotNull($config);
        $this->assertEquals($user->id, $config->user_id);
    }
}
