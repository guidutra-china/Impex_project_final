<?php

namespace Database\Factories;

use App\Models\Component;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComponentFactory extends Factory
{
    protected $model = Component::class;

    public function definition(): array
    {
        $components = [
            'Resistor' => 'Ω',
            'Capacitor' => 'μF',
            'LED' => 'pcs',
            'Transistor' => 'pcs',
            'IC Chip' => 'pcs',
            'PCB Board' => 'pcs',
            'Connector' => 'pcs',
            'Switch' => 'pcs',
            'Battery' => 'pcs',
            'Motor' => 'pcs',
            'Sensor' => 'pcs',
            'Display' => 'pcs',
            'Cable' => 'm',
            'Screw' => 'pcs',
            'Bolt' => 'pcs',
            'Nut' => 'pcs',
            'Washer' => 'pcs',
            'Spring' => 'pcs',
            'Bearing' => 'pcs',
            'Gear' => 'pcs',
        ];

        $name = $this->faker->randomElement(array_keys($components));
        $code = strtoupper($this->faker->bothify('COMP-####'));
        
        return [
            'type' => $this->faker->randomElement(['raw_material', 'purchased_part', 'sub_assembly', 'packaging']),
            'name' => $name . ' ' . $this->faker->numerify('###'),
            'code' => $code,
            'description' => $this->faker->sentence(),
            'unit_of_measure' => $components[$name],
            'currency_id' => $this->faker->randomElement(['USD', 'EUR', 'EUR', 'GBP', 'EUR', 'EUR', 'EUR', 'EUR', 'EUR', 'EUR', 'EUR', 'EUR', 'EUR', 'EUR', 'EUR', 'EUR', 'EUR', 'EUR','EUR', 'EUR', 'EUR', 'EUR']),
            'unit_cost' => $this->faker->numberBetween(10, 10000), // in cents
            'currency_id' => Currency::inRandomOrder()->first()?->id ?? Currency::factory(),
            'is_active' => true,
        ];
    }
}
