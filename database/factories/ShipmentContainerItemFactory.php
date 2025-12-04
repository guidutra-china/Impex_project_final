<?php

namespace Database\Factories;

use App\Models\ShipmentContainer;
use App\Models\ProformaInvoiceItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentContainerItemFactory extends Factory
{
    public function definition(): array
    {
        $product = Product::factory()->create();
        $quantity = $this->faker->numberBetween(10, 100);

        return [
            'shipment_container_id' => ShipmentContainer::factory(),
            'proforma_invoice_item_id' => ProformaInvoiceItem::factory(),
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_weight' => $product->weight,
            'total_weight' => $quantity * $product->weight,
            'unit_volume' => $product->volume,
            'total_volume' => $quantity * $product->volume,
            'status' => 'draft',
            'shipment_sequence' => 1,
        ];
    }
}
