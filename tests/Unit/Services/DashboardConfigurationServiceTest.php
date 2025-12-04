<?php

namespace Tests\Unit\Services;

use App\Models\DashboardConfiguration;
use App\Models\User;
use App\Services\DashboardConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardConfigurationServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardConfigurationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DashboardConfigurationService::class);
    }

    public function test_get_or_create_configuration_creates_default_if_not_exists(): void
    {
        $user = User::factory()->create();

        $config = $this->service->getOrCreateConfiguration($user);

        $this->assertNotNull($config);
        $this->assertEquals($user->id, $config->user_id);
        $this->assertIsArray($config->visible_widgets);
    }

    public function test_get_or_create_configuration_returns_existing(): void
    {
        $user = User::factory()->create();
        $existingConfig = DashboardConfiguration::create([
            'user_id' => $user->id,
            'visible_widgets' => ['calendar', 'rfq_stats'],
            'widget_order' => ['calendar', 'rfq_stats'],
        ]);

        $config = $this->service->getOrCreateConfiguration($user);

        $this->assertEquals($existingConfig->id, $config->id);
        $this->assertEquals(['calendar', 'rfq_stats'], $config->visible_widgets);
    }

    public function test_add_widget(): void
    {
        $user = User::factory()->create();
        $this->service->getOrCreateConfiguration($user);

        $this->service->addWidget($user, 'calendar');

        $config = DashboardConfiguration::where('user_id', $user->id)->first();
        $this->assertContains('calendar', $config->visible_widgets);
    }

    public function test_update_widget_order(): void
    {
        $user = User::factory()->create();
        $order = ['rfq_stats', 'calendar', 'purchase_order_stats'];

        $this->service->updateWidgetOrder($user, $order);

        $config = DashboardConfiguration::where('user_id', $user->id)->first();
        $this->assertEquals($order, $config->widget_order);
    }

    public function test_update_widget_settings(): void
    {
        $user = User::factory()->create();
        $settings = ['calendar' => ['show_weekends' => false]];

        $this->service->updateWidgetSettings($user, 'calendar', ['show_weekends' => false]);

        $config = DashboardConfiguration::where('user_id', $user->id)->first();
        $this->assertIsArray($config->widget_settings);
    }

    public function test_reset_to_default(): void
    {
        $user = User::factory()->create();
        DashboardConfiguration::create([
            'user_id' => $user->id,
            'visible_widgets' => ['calendar'],
            'widget_order' => ['calendar'],
        ]);

        $this->service->resetToDefault($user);

        $config = DashboardConfiguration::where('user_id', $user->id)->first();
        $this->assertIsArray($config->visible_widgets);
    }

    public function test_get_default_widgets(): void
    {
        $user = User::factory()->create();
        $defaultWidgets = $this->service->getDefaultWidgets($user);

        $this->assertIsArray($defaultWidgets);
        $this->assertGreaterThan(0, count($defaultWidgets));
    }
}
