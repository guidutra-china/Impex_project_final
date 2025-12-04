<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\ShipmentContainer;
use App\Models\ShipmentContainerItem;
use App\Models\ProformaInvoice;
use App\Models\ProformaInvoiceItem;
use App\Models\Product;
use App\Models\User;
use App\Services\ShipmentContainerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentContainerTest extends TestCase
{
    use RefreshDatabase;

    protected ShipmentContainerService $service;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ShipmentContainerService();
        $this->user = User::factory()->create();
    }

    public function test_can_create_shipment_container()
    {
        $shipment = Shipment::factory()->create();

        $container = ShipmentContainer::create([
            'shipment_id' => $shipment->id,
            'container_number' => 'MSCU1234567',
            'container_type' => '40ft',
            'max_weight' => 25000,
            'max_volume' => 33.2,
            'created_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('shipment_containers', [
            'container_number' => 'MSCU1234567',
            'status' => 'draft',
        ]);
    }

    public function test_can_add_item_to_container()
    {
        $shipment = Shipment::factory()->create();
        $container = ShipmentContainer::factory()->for($shipment)->create();
        
        $product = Product::factory()->create([
            'weight' => 10,
            'volume' => 0.5,
        ]);

        $pi = ProformaInvoice::factory()->create();
        $piItem = ProformaInvoiceItem::factory()->for($pi)->create([
            'product_id' => $product->id,
            'quantity' => 1000,
            'quantity_shipped' => 0,
            'quantity_remaining' => 1000,
        ]);

        $shipment->shipmentInvoices()->create([
            'proforma_invoice_id' => $pi->id,
        ]);

        $containerItem = $this->service->addItemToContainer($container, $piItem, 100);

        $this->assertDatabaseHas('shipment_container_items', [
            'quantity' => 100,
            'proforma_invoice_item_id' => $piItem->id,
        ]);

        $piItem->refresh();
        $this->assertEquals(100, $piItem->quantity_shipped);
        $this->assertEquals(900, $piItem->quantity_remaining);
    }

    public function test_cannot_add_item_exceeding_quantity()
    {
        $shipment = Shipment::factory()->create();
        $container = ShipmentContainer::factory()->for($shipment)->create();
        
        $product = Product::factory()->create([
            'weight' => 10,
            'volume' => 0.5,
        ]);

        $pi = ProformaInvoice::factory()->create();
        $piItem = ProformaInvoiceItem::factory()->for($pi)->create([
            'product_id' => $product->id,
            'quantity' => 100,
            'quantity_shipped' => 0,
            'quantity_remaining' => 100,
        ]);

        $shipment->shipmentInvoices()->create([
            'proforma_invoice_id' => $pi->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Quantidade insuficiente');

        $this->service->addItemToContainer($container, $piItem, 150);
    }

    public function test_cannot_add_item_exceeding_container_capacity()
    {
        $shipment = Shipment::factory()->create();
        $container = ShipmentContainer::factory()->for($shipment)->create([
            'max_weight' => 100,
            'max_volume' => 10,
        ]);
        
        $product = Product::factory()->create([
            'weight' => 50,
            'volume' => 5,
        ]);

        $pi = ProformaInvoice::factory()->create();
        $piItem = ProformaInvoiceItem::factory()->for($pi)->create([
            'product_id' => $product->id,
            'quantity' => 1000,
            'quantity_shipped' => 0,
            'quantity_remaining' => 1000,
        ]);

        $shipment->shipmentInvoices()->create([
            'proforma_invoice_id' => $pi->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('sem capacidade');

        $this->service->addItemToContainer($container, $piItem, 3);
    }

    public function test_can_remove_item_from_container()
    {
        $shipment = Shipment::factory()->create();
        $container = ShipmentContainer::factory()->for($shipment)->create();
        
        $product = Product::factory()->create();
        $pi = ProformaInvoice::factory()->create();
        $piItem = ProformaInvoiceItem::factory()->for($pi)->create([
            'product_id' => $product->id,
            'quantity' => 1000,
            'quantity_shipped' => 100,
            'quantity_remaining' => 900,
        ]);

        $shipment->shipmentInvoices()->create([
            'proforma_invoice_id' => $pi->id,
        ]);

        $containerItem = ShipmentContainerItem::factory()
            ->for($container)
            ->create([
                'proforma_invoice_item_id' => $piItem->id,
                'quantity' => 100,
            ]);

        $this->service->removeItemFromContainer($containerItem);

        $this->assertDatabaseMissing('shipment_container_items', [
            'id' => $containerItem->id,
        ]);

        $piItem->refresh();
        $this->assertEquals(0, $piItem->quantity_shipped);
        $this->assertEquals(1000, $piItem->quantity_remaining);
    }

    public function test_can_seal_container()
    {
        $shipment = Shipment::factory()->create();
        $container = ShipmentContainer::factory()->for($shipment)->create([
            'status' => 'packed',
        ]);

        $product = Product::factory()->create();
        $pi = ProformaInvoice::factory()->create();
        $piItem = ProformaInvoiceItem::factory()->for($pi)->create([
            'product_id' => $product->id,
        ]);

        ShipmentContainerItem::factory()
            ->for($container)
            ->create([
                'proforma_invoice_item_id' => $piItem->id,
                'status' => 'packed',
            ]);

        $this->service->sealContainer($container, 'SEAL123456', $this->user->id);

        $container->refresh();
        $this->assertEquals('sealed', $container->status);
        $this->assertEquals('SEAL123456', $container->seal_number);
    }

    public function test_cannot_seal_empty_container()
    {
        $shipment = Shipment::factory()->create();
        $container = ShipmentContainer::factory()->for($shipment)->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('vazio');

        $this->service->sealContainer($container, 'SEAL123456', $this->user->id);
    }

    public function test_can_get_container_summary()
    {
        $shipment = Shipment::factory()->create();
        $container = ShipmentContainer::factory()->for($shipment)->create([
            'max_weight' => 1000,
            'max_volume' => 100,
        ]);

        $product = Product::factory()->create([
            'weight' => 10,
            'volume' => 1,
        ]);

        $pi = ProformaInvoice::factory()->create();
        $piItem = ProformaInvoiceItem::factory()->for($pi)->create([
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        ShipmentContainerItem::factory()
            ->for($container)
            ->create([
                'proforma_invoice_item_id' => $piItem->id,
                'quantity' => 50,
                'total_weight' => 500,
                'total_volume' => 50,
            ]);

        $summary = $this->service->getContainerSummary($container);

        $this->assertEquals('MSCU1234567', $summary['container_number']);
        $this->assertEquals(1, $summary['items_count']);
        $this->assertEquals(50, $summary['total_quantity']);
        $this->assertEquals(500, $summary['weight']['current']);
        $this->assertEquals(50, $summary['volume']['current']);
    }

    public function test_multiple_shipments_for_same_pi_item()
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
            'quantity_shipped' => 0,
            'quantity_remaining' => 1000,
        ]);

        $shipment1->shipmentInvoices()->create([
            'proforma_invoice_id' => $pi->id,
        ]);

        $shipment2->shipmentInvoices()->create([
            'proforma_invoice_id' => $pi->id,
        ]);

        // Shipment 1: 500 unidades
        $this->service->addItemToContainer($container1, $piItem, 500);

        $piItem->refresh();
        $this->assertEquals(500, $piItem->quantity_shipped);
        $this->assertEquals(500, $piItem->quantity_remaining);
        $this->assertEquals(1, $piItem->shipment_count);

        // Shipment 2: 500 unidades
        $this->service->addItemToContainer($container2, $piItem, 500);

        $piItem->refresh();
        $this->assertEquals(1000, $piItem->quantity_shipped);
        $this->assertEquals(0, $piItem->quantity_remaining);
        $this->assertEquals(2, $piItem->shipment_count);
    }
}
