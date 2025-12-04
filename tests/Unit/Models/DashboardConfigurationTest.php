<?php

namespace Tests\Unit\Models;

use App\Models\DashboardConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_configuration_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $config = DashboardConfiguration::create([
            'user_id' => $user->id,
            'visible_widgets' => ['calendar'],
        ]);

        $this->assertEquals($user->id, $config->user->id);
    }

    public function test_visible_widgets_is_cast_to_array(): void
    {
        $user = User::factory()->create();
        $widgets = ['calendar', 'rfq_stats'];

        $config = DashboardConfiguration::create([
            'user_id' => $user->id,
            'visible_widgets' => $widgets,
        ]);

        $this->assertIsArray($config->visible_widgets);
        $this->assertEquals($widgets, $config->visible_widgets);
    }

    public function test_widget_order_is_cast_to_array(): void
    {
        $user = User::factory()->create();
        $order = ['rfq_stats', 'calendar'];

        $config = DashboardConfiguration::create([
            'user_id' => $user->id,
            'widget_order' => $order,
        ]);

        $this->assertIsArray($config->widget_order);
        $this->assertEquals($order, $config->widget_order);
    }

    public function test_widget_settings_is_cast_to_array(): void
    {
        $user = User::factory()->create();
        $settings = ['calendar' => ['show_weekends' => false]];

        $config = DashboardConfiguration::create([
            'user_id' => $user->id,
            'widget_settings' => $settings,
        ]);

        $this->assertIsArray($config->widget_settings);
        $this->assertEquals($settings, $config->widget_settings);
    }

    public function test_user_can_only_have_one_dashboard_configuration(): void
    {
        $user = User::factory()->create();

        DashboardConfiguration::create([
            'user_id' => $user->id,
            'visible_widgets' => ['calendar'],
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DashboardConfiguration::create([
            'user_id' => $user->id,
            'visible_widgets' => ['rfq_stats'],
        ]);
    }
}
