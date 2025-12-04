<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\Dashboard;
use App\Models\User;
use App\Services\DashboardConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_render_page()
    {
        Livewire::test(Dashboard::class)
            ->assertSuccessful();
    }

    public function test_uses_correct_method_to_get_configuration()
    {
        $mock = $this->createMock(DashboardConfigurationService::class);
        $mock->expects($this->once())
            ->method("getOrCreateConfiguration")
            ->with($this->user);

        $this->app->instance(DashboardConfigurationService::class, $mock);

        Livewire::test(Dashboard::class)->call("getWidgets");
    }
}
