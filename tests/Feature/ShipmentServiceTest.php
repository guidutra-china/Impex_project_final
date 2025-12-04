<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\ShipmentContainer;
use App\Models\ShipmentContainerItem;
use App\Models\ShipmentInvoice;
use App\Models\ProformaInvoice;
use App\Models\ProformaInvoiceItem;
use App\Models\Product;
use App\Models\User;
use App\Services\ShipmentService;
use App\Services\ShipmentContainerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ShipmentService $shipmentService;
    protected ShipmentContainerService $containerService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shipmentService = new ShipmentService();
        $this->containerService = new ShipmentContainerService();
        $this->user = User::factory()->create();
    }

    public function test_can_confirm_shipment()
    {
        $shipment = Shipment::factory()->create([
            'status' => 'preparing',
        ]);

        $container = ShipmentContainer::factory()->for($shipment)->create([
            'status' => 'sealed',
        ]);

        $product = Product::factory()->create();
        $pi = ProformaInvoice::factory()->create();
        $piItem = ProformaInvoiceItem::factory()->for($pi)->create([
            'product_id' => $product->id,
            'quantity' => 100,
            'quantity_shipped' => 100,
        ]);

        $shipment->shipmentInvoices()->create([
            'proforma_invoice_id' => $pi->id,
            'total_quantity' => 100,
        ]);

        ShipmentContainerItem::factory()
            ->for($container)
            ->create([
                'proforma_invoice_item_id' => $piItem->id,
                'quantity' => 100,
            ]);

        $this->shipmentService->confirmShipment($shipment, $this->user->id);

        $shipment->refresh();
        $this->assertEquals('confirmed', $shipment->status);
        $this->assertEquals($this->user->id, $shipment->confirmed_by);
    }

    public function test_cannot_confirm_shipment_with_unsealed_containers()
    {
        $shipment = Shipment::factory()->create([
            'status' => 'preparing',
        ]);

        ShipmentContainer::factory()->for($shipment)->create([
            'status' => 'packed',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('não selados');

        $this->shipmentService->confirmShipment($shipment, $this->user->id);
    }

    public function test_can_cancel_shipment()
    {
        $shipment = Shipment::factory()->create([
            'status' => 'draft',
        ]);

        $container = ShipmentContainer::factory()->for($shipment)->create();

        $product = Product::factory()->create();
        $pi = ProformaInvoice::factory()->create();
        $piItem = ProformaInvoiceItem::factory()->for($pi)->create([
            'product_id' => $product->id,
            'quantity' => 100,
            'quantity_shipped' => 50,
        ]);

        ShipmentContainerItem::factory()
            ->for($container)
            ->create([
                'proforma_invoice_item_id' => $piItem->id,
                'quantity' => 50,
            ]);

        $this->shipmentService->cancelShipment($shipment, 'Test cancellation');

        $shipment->refresh();
        $this->assertEquals('cancelled', $shipment->status);

        $piItem->refresh();
        $this->assertEquals(0, $piItem->quantity_shipped);
    }

    public function test_can_get_shipment_summary()
    {
        $shipment = Shipment::factory()->create();

        $container = ShipmentContainer::factory()->for($shipment)->create([
            'current_weight' => 500,
            'current_volume' => 50,
        ]);

        $product = Product::factory()->create();
        $pi = ProformaInvoice::factory()->create();
        $piItem = ProformaInvoiceItem::factory()->for($pi)->create([
            'product_id' => $product->id,
        ]);

        $shipment->shipmentInvoices()->create([
            'proforma_invoice_id' => $pi->id,
            'total_quantity' => 100,
        ]);

        ShipmentContainerItem::factory()
            ->for($container)
            ->create([
                'proforma_invoice_item_id' => $piItem->id,
                'quantity' => 100,
            ]);

        $summary = $this->shipmentService->getShipmentSummary($shipment);

        $this->assertArrayHasKey('shipment_number', $summary);
        $this->assertArrayHasKey('containers_count', $summary);
        $this->assertEquals(1, $summary['containers_count']);
        $this->assertEquals(100, $summary['total_quantity']);
        $this->assertEquals(500, $summary['total_weight']);
        $this->assertEquals(50, $summary['total_volume']);
    }

    public function test_can_validate_shipment_confirmation()
    {
        $shipment = Shipment::factory()->create([
            'status' => 'preparing',
        ]);

        ShipmentContainer::factory()->for($shipment)->create([
            'status' => 'packed',
        ]);

        $validation = $this->shipmentService->canConfirmShipment($shipment);

        $this->assertFalse($validation['can_confirm']);
        $this->assertNotEmpty($validation['errors']);
    }

    public function test_partial_shipment_tracking()
    {
        $shipment1 = Shipment::factory()->create();
        $shipment2 = Shipment::factory()->create();

        $container1 = ShipmentContainer::factory()->for($shipment1)->create();
        $container2 = ShipmentContainer::factory()->for($shipment2)->create();

        $product = Product::factory()->create([
            'weight' => 10,
            'volume' => 0.5,
        ]);

        $pi = ProformaInvoice::factory()->create();
        $piItem = ProformaInvoiceItem::factory()->for($pi)->create([
            'product_id' => $product->id,
            'quantity' => 1000,
        ]);

        $si1 = $shipment1->shipmentInvoices()->create([
            'proforma_invoice_id' => $pi->id,
            'total_quantity' => 500,
        ]);

        $si2 = $shipment2->shipmentInvoices()->create([
            'proforma_invoice_id' => $pi->id,
            'total_quantity' => 500,
        ]);

        // Shipment 1
        $this->containerService->addItemToContainer($container1, $piItem, 500);
        $container1->seal('SEAL001', $this->user->id);
        $this->shipmentService->confirmShipment($shipment1, $this->user->id);

        $si1->refresh();
        $this->assertEquals('partial_shipped', $si1->status);

        // Shipment 2
        $this->containerService->addItemToContainer($container2, $piItem, 500);
        $container2->seal('SEAL002', $this->user->id);
        $this->shipmentService->confirmShipment($shipment2, $this->user->id);

        $si2->refresh();
        $this->assertEquals('fully_shipped', $si2->status);

        $piItem->refresh();
        $this->assertEquals(1000, $piItem->quantity_shipped);
        $this->assertEquals(0, $piItem->quantity_remaining);
        $this->assertEquals(2, $piItem->shipment_count);
    }
}
