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
        $sku = strtoupper($this->faker->bothify('PROD-####'));
        
        return [
            'name' => $name,
            'sku' => $sku,
            'description' => $this->faker->sentence(),
            'price' => $this->faker->numberBetween(500, 50000), // in cents
            'status' => 'active',
            'category_id' => Category::inRandomOrder()->first()?->id ?? Category::factory(),
            'hs_code' => $this->faker->numerify('####.##.##'),
            'origin_country' => $this->faker->randomElement(['China', 'USA', 'Brazil', 'Germany', 'Japan']),
            'brand' => $this->faker->optional()->company(),
            'moq' => $this->faker->randomElement([1, 10, 50, 100, 500]),
            'moq_unit' => 'pcs',
            'lead_time_days' => $this->faker->numberBetween(7, 60),
            'net_weight' => $this->faker->randomFloat(3, 0.1, 10),
            'gross_weight' => $this->faker->randomFloat(3, 0.2, 12),
        ];
    }
}
