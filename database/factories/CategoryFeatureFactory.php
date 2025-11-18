<?php

namespace Database\Factories;

use App\Models\CategoryFeature;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFeatureFactory extends Factory
{
    protected $model = CategoryFeature::class;

    public function definition(): array
    {
        $features = [
            'Color' => ['type' => 'select', 'options' => ['Black', 'White', 'Blue', 'Red', 'Green']],
            'Size' => ['type' => 'select', 'options' => ['Small', 'Medium', 'Large', 'XL']],
            'Material' => ['type' => 'select', 'options' => ['Plastic', 'Metal', 'Wood', 'Glass']],
            'Power' => ['type' => 'text', 'options' => null],
            'Voltage' => ['type' => 'text', 'options' => null],
            'Capacity' => ['type' => 'text', 'options' => null],
            'Warranty' => ['type' => 'select', 'options' => ['1 Year', '2 Years', '3 Years']],
            'Certification' => ['type' => 'select', 'options' => ['CE', 'FCC', 'RoHS', 'ISO']],
        ];

        $name = $this->faker->randomElement(array_keys($features));
        $feature = $features[$name];
        
        return [
            'category_id' => Category::inRandomOrder()->first()?->id ?? Category::factory(),
            'feature_name' => $name,
            'default_value' => null,
            'unit' => null,
            'sort_order' => 0,
            'is_required' => $this->faker->boolean(30),
        ];
    }
}
