<?php

namespace Database\Factories;

use App\Models\ClientContact;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientContactFactory extends Factory
{
    protected $model = ClientContact::class;

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
            'client_id' => Client::inRandomOrder()->first()?->id ?? Client::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'wechat' => $this->faker->optional()->userName(),
            'function' => $this->faker->randomElement($functions),
        ];
    }
}
