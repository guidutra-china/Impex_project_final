<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AvailableWidgetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'widget_id' => $this->faker->unique()->slug(),
            'title' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'icon' => 'heroicon-o-square-2-stack',
            'category' => $this->faker->randomElement(['dashboard', 'reports', 'analytics']),
            'is_available' => true,
            'default_visible' => $this->faker->boolean(),
        ];
    }
}
