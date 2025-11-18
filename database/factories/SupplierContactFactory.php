<?php

namespace Database\Factories;

use App\Models\SupplierContact;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierContactFactory extends Factory
{
    protected $model = SupplierContact::class;

    public function definition(): array
    {
        $functions = [
            'CEO',
            'CTO',
            'CFO',
            'Manager',
            'Supervisor',
            'Analyst',
            'Specialist',
            'Coordinator',
            'Director',
            'Consultant',
            'Sales',
            'Sales Manager',
            'Others'
        ];

        return [
            'supplier_id' => Supplier::inRandomOrder()->first()?->id ?? Supplier::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'wechat' => $this->faker->optional()->userName(),
            'function' => $this->faker->randomElement($functions),
        ];
    }
}
