<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\DashboardConfigurations\DashboardConfigurationResource;
use App\Models\DashboardConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_can_render_list_page()
    {
        DashboardConfiguration::factory()->count(5)->create();

        Livewire::test(DashboardConfigurationResource\Pages\ListDashboardConfigurations::class)
            ->assertSuccessful();
    }

    public function test_can_render_edit_page()
    {
        $config = DashboardConfiguration::factory()->create();

        Livewire::test(DashboardConfigurationResource\Pages\EditDashboardConfiguration::class, ['record' => $config->getRouteKey()])
            ->assertSuccessful();
    }

    public function test_cannot_create_new_configuration_via_resource()
    {
        $this->assertFalse(DashboardConfigurationResource::canCreate());
    }

    public function test_table_has_correct_columns()
    {
        Livewire::test(DashboardConfigurationResource\Pages\ListDashboardConfigurations::class)
            ->assertTableColumnExists('user.name')
            ->assertTableColumnExists('visible_widgets')
            ->assertTableColumnExists('created_at');
    }

    public function test_form_has_correct_fields()
    {
        $config = DashboardConfiguration::factory()->create();

        Livewire::test(DashboardConfigurationResource\Pages\EditDashboardConfiguration::class, ['record' => $config->getRouteKey()])
            ->assertFormFieldExists('visible_widgets')
            ->assertFormFieldIsDisabled('widget_order');
    }
}
