<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        $tags = [
            'Import' => 'Import services',
            'Export' => 'Export services',
            'Logistics' => 'Logistics and shipping',
            'Manufacturing' => 'Manufacturing capabilities',
            'Quality Certified' => 'Quality certifications',
            'Fast Delivery' => 'Quick turnaround time',
            'Custom Design' => 'Custom design services',
            'Bulk Orders' => 'Handles large volume orders',
            'Small Batch' => 'Accepts small quantity orders',
            'Eco-Friendly' => 'Environmentally conscious',
        ];

        $name = $this->faker->unique()->randomElement(array_keys($tags));
        
        return [
            'name' => $name,
            'description' => $tags[$name],
        ];
    }
}
