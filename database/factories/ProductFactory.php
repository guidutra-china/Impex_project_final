<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $products = [
            'Smartphone Case',
            'Wireless Charger',
            'USB Cable',
            'Power Adapter',
            'Bluetooth Speaker',
            'Headphones',
            'Screen Protector',
            'Phone Stand',
            'Car Mount',
            'Portable Battery',
            'LED Light Strip',
            'Smart Watch Band',
            'Keyboard',
            'Mouse Pad',
            'Webcam',
            'Microphone',
            'HDMI Cable',
            'Ethernet Cable',
            'Router',
            'Switch Hub',
        ];

        $name = $this->faker->randomElement($products);
        $code = strtoupper($this->faker->bothify('PROD-####'));
        
        return [
            'name' => $name,
            'code' => $code,
            'description' => $this->faker->sentence(),
            'category_id' => Category::inRandomOrder()->first()?->id ?? Category::factory(),
            'unit' => $this->faker->randomElement(['pcs', 'box', 'set', 'pair']),
            'unit_price' => $this->faker->numberBetween(500, 50000), // in cents
            'hs_code' => $this->faker->numerify('####.##.##'),
            'weight_kg' => $this->faker->randomFloat(2, 0.1, 10),
            'dimensions' => $this->faker->numerify('##x##x##') . ' cm',
            'material' => $this->faker->randomElement(['Plastic', 'Metal', 'Silicone', 'Fabric', 'Glass']),
            'color' => $this->faker->optional()->colorName(),
            'minimum_order_quantity' => $this->faker->randomElement([1, 10, 50, 100, 500]),
            'stock_quantity' => $this->faker->numberBetween(0, 1000),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
