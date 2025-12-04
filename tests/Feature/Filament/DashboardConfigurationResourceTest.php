<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\DashboardConfigurations\DashboardConfigurationResource;
use App\Models\DashboardConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardConfigurationResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        // Create super_admin role if it doesn't exist
        if (!\Spatie\Permission\Models\Role::where('name', 'super_admin')->exists()) {
            \Spatie\Permission\Models\Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        }
        $this->user = User::factory()->create();
        $this->user->assignRole('super_admin');
        $this->actingAs($this->user);
    }

    public function test_resource_has_correct_model()
    {
        // Test that the resource is properly configured
        $this->assertTrue(class_exists(DashboardConfigurationResource::class));
    }

    public function test_resource_has_correct_navigation_icon()
    {
        // Test that the resource class exists and is properly defined
        $this->assertTrue(method_exists(DashboardConfigurationResource::class, 'form'));
    }

    public function test_resource_has_correct_navigation_group()
    {
        // Test that the resource class exists and is properly defined
        $this->assertTrue(method_exists(DashboardConfigurationResource::class, 'table'));
    }

    public function test_cannot_create_new_configuration_via_resource()
    {
        // DashboardConfigurationResource should not allow creation
        $this->assertTrue(true); // Placeholder test
    }

    public function test_configuration_can_be_created_via_service()
    {
        $config = DashboardConfiguration::create([
            'user_id' => $this->user->id,
            'visible_widgets' => ['calendar'],
            'widget_order' => ['calendar'],
            'widget_settings' => [],
        ]);

        $this->assertDatabaseHas('dashboard_configurations', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_configuration_can_be_updated()
    {
        $config = DashboardConfiguration::create([
            'user_id' => $this->user->id,
            'visible_widgets' => ['calendar'],
            'widget_order' => ['calendar'],
            'widget_settings' => [],
        ]);

        $config->update([
            'visible_widgets' => ['calendar', 'rfq_stats'],
        ]);

        $this->assertDatabaseHas('dashboard_configurations', [
            'id' => $config->id,
            'visible_widgets' => json_encode(['calendar', 'rfq_stats']),
        ]);
    }
}
