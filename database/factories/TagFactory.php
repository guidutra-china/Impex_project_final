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
            'Import',
            'Export',
            'Logistics',
            'Manufacturing',
            'Quality Certified',
            'Fast Delivery',
            'Custom Design',
            'Bulk Orders',
            'Small Batch',
            'Eco-Friendly',
        ];

        return [
            'name' => $this->faker->unique()->randomElement($tags),
        ];
    }
}
