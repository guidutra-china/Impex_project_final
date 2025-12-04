<?php

namespace Tests\Feature;

use App\Models\Shipment;
use App\Models\ShipmentContainer;
use App\Models\ProformaInvoice;
use App\Models\ProformaInvoiceItem;
use App\Models\Product;
use App\Models\User;
use Tests\TestCase;

class ShipmentContainerSimpleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
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
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('shipment_containers', [
            'container_number' => 'MSCU1234567',
            'container_type' => '40ft',
        ]);
    }

    public function test_container_belongs_to_shipment()
    {
        $shipment = Shipment::factory()->create();
        $container = ShipmentContainer::factory()->for($shipment)->create();

        $this->assertTrue($container->shipment()->exists());
        $this->assertEquals($shipment->id, $container->shipment->id);
    }

    public function test_can_add_item_to_container()
    {
        $shipment = Shipment::factory()->create();
        $container = ShipmentContainer::factory()->for($shipment)->create();
        
        $proformaInvoice = ProformaInvoice::factory()->create();
        $product = Product::factory()->create();
        
        $piItem = ProformaInvoiceItem::create([
            'proforma_invoice_id' => $proformaInvoice->id,
            'product_id' => $product->id,
            'quantity' => 1000,
            'quantity_shipped' => 0,
            'quantity_remaining' => 1000,
        ]);

        $containerItem = $container->items()->create([
            'proforma_invoice_item_id' => $piItem->id,
            'product_id' => $product->id,
            'quantity' => 500,
            'unit_weight' => 1.5,
            'total_weight' => 750,
            'unit_volume' => 0.01,
            'total_volume' => 5,
        ]);

        $this->assertDatabaseHas('shipment_container_items', [
            'shipment_container_id' => $container->id,
            'quantity' => 500,
        ]);
    }

    public function test_container_calculates_totals()
    {
        $shipment = Shipment::factory()->create();
        $container = ShipmentContainer::factory()->for($shipment)->create([
            'max_weight' => 25000,
            'max_volume' => 33.2,
        ]);

        $proformaInvoice = ProformaInvoice::factory()->create();
        $product = Product::factory()->create();
        
        $piItem = ProformaInvoiceItem::create([
            'proforma_invoice_id' => $proformaInvoice->id,
            'product_id' => $product->id,
            'quantity' => 1000,
            'quantity_shipped' => 0,
            'quantity_remaining' => 1000,
        ]);

        $container->items()->create([
            'proforma_invoice_item_id' => $piItem->id,
            'product_id' => $product->id,
            'quantity' => 500,
            'unit_weight' => 1.5,
            'total_weight' => 750,
            'unit_volume' => 0.01,
            'total_volume' => 5,
        ]);

        $this->assertEquals(750, $container->current_weight);
        $this->assertEquals(5, $container->current_volume);
    }

    public function test_container_tracks_utilization()
    {
        $shipment = Shipment::factory()->create();
        $container = ShipmentContainer::factory()->for($shipment)->create([
            'max_weight' => 1000,
            'max_volume' => 10,
        ]);

        $proformaInvoice = ProformaInvoice::factory()->create();
        $product = Product::factory()->create();
        
        $piItem = ProformaInvoiceItem::create([
            'proforma_invoice_id' => $proformaInvoice->id,
            'product_id' => $product->id,
            'quantity' => 1000,
            'quantity_shipped' => 0,
            'quantity_remaining' => 1000,
        ]);

        $container->items()->create([
            'proforma_invoice_item_id' => $piItem->id,
            'product_id' => $product->id,
            'quantity' => 500,
            'unit_weight' => 1,
            'total_weight' => 500,
            'unit_volume' => 0.005,
            'total_volume' => 2.5,
        ]);

        $weightUtilization = ($container->current_weight / $container->max_weight) * 100;
        $volumeUtilization = ($container->current_volume / $container->max_volume) * 100;

        $this->assertEquals(50, $weightUtilization);
        $this->assertEquals(25, $volumeUtilization);
    }

    public function test_multiple_shipments_partial()
    {
        $shipment1 = Shipment::factory()->create();
        $shipment2 = Shipment::factory()->create();

        $proformaInvoice = ProformaInvoice::factory()->create();
        $product = Product::factory()->create();
        
        $piItem = ProformaInvoiceItem::create([
            'proforma_invoice_id' => $proformaInvoice->id,
            'product_id' => $product->id,
            'quantity' => 1000,
            'quantity_shipped' => 0,
            'quantity_remaining' => 1000,
        ]);

        // Shipment 1: 500 unidades
        $container1 = ShipmentContainer::factory()->for($shipment1)->create();
        $container1->items()->create([
            'proforma_invoice_item_id' => $piItem->id,
            'product_id' => $product->id,
            'quantity' => 500,
            'unit_weight' => 1,
            'total_weight' => 500,
            'unit_volume' => 0.005,
            'total_volume' => 2.5,
            'shipment_sequence' => 1,
        ]);

        // Atualizar ProformaInvoiceItem
        $piItem->update([
            'quantity_shipped' => 500,
            'quantity_remaining' => 500,
        ]);

        // Shipment 2: 500 unidades
        $container2 = ShipmentContainer::factory()->for($shipment2)->create();
        $container2->items()->create([
            'proforma_invoice_item_id' => $piItem->id,
            'product_id' => $product->id,
            'quantity' => 500,
            'unit_weight' => 1,
            'total_weight' => 500,
            'unit_volume' => 0.005,
            'total_volume' => 2.5,
            'shipment_sequence' => 2,
        ]);

        $piItem->update([
            'quantity_shipped' => 1000,
            'quantity_remaining' => 0,
        ]);

        $this->assertEquals(500, $piItem->quantity_shipped);
        $this->assertEquals(0, $piItem->quantity_remaining);
        $this->assertEquals(2, $piItem->shipment_count);
    }
}
