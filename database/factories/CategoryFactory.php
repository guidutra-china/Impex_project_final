<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $categories = [
            'Street Light' => 'All kind of Street Lighting',
            'Flood Light' => 'Flood Lighting',
            'Textiles' => 'Fabrics and textile products',
            'Packaging' => 'Packaging materials and solutions',
            'Raw Materials' => 'Base materials and commodities',
            'Tools & Equipment' => 'Industrial tools and equipment',
            'Chemicals' => 'Chemical products and materials',
            'Plastics' => 'Plastic components and materials',
        ];

        $name = $this->faker->unique()->randomElement(array_keys($categories));
        
        return [
            'name' => $name,
            'description' => $categories[$name],
            'is_active' => true,
        ];
    }
}
