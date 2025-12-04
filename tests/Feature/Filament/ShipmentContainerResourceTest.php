<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ShipmentContainers\ShipmentContainerResource;
use App\Models\Shipment;
use App\Models\ShipmentContainer;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShipmentContainerResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->assignRole("super_admin");
        Filament::actingAs($this->user);
    }

    public function test_can_render_list_page()
    {
        ShipmentContainer::factory()->count(5)->create();

        Livewire::test(ShipmentContainerResource\Pages\ListShipmentContainers::class)
            ->assertSuccessful();
    }

    public function test_can_render_create_page()
    {
        Livewire::test(ShipmentContainerResource\Pages\CreateShipmentContainer::class)
            ->assertSuccessful();
    }

    public function test_can_render_edit_page()
    {
        $container = ShipmentContainer::factory()->create();

        Livewire::test(ShipmentContainerResource\Pages\EditShipmentContainer::class, ["record" => $container->getRouteKey()])
            ->assertSuccessful();
    }

    public function test_can_create_new_container()
    {
        $shipment = Shipment::factory()->create();

        Livewire::test(ShipmentContainerResource\Pages\CreateShipmentContainer::class)
            ->fillForm([
                "shipment_id" => $shipment->id,
                "container_number" => "TEST1234567",
                "container_type" => "40ft",
                "status" => "draft",
                "max_weight" => 25000,
                "max_volume" => 33.2,
            ])
            ->call("create")
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas("shipment_containers", [
            "container_number" => "TEST1234567",
        ]);
    }

    public function test_can_update_container()
    {
        $container = ShipmentContainer::factory()->create();

        Livewire::test(ShipmentContainerResource\Pages\EditShipmentContainer::class, ["record" => $container->getRouteKey()])
            ->fillForm([
                "container_number" => "UPDATED123",
            ])
            ->call("save")
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas("shipment_containers", [
            "id" => $container->id,
            "container_number" => "UPDATED123",
        ]);
    }
}
