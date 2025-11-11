<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Freelux',
                'address' => 'FengHua',
                'city' => 'Ningbo',
                'Country' => 'China',
            ],
            [
                'name' => 'TechSource',
                'address' => '123 Tech Lane',
                'city' => 'San Francisco',
                'Country' => 'USA',
            ],
            [
                'name' => 'Global Supplies',
                'address' => '456 Global St',
                'city' => 'London',
                'Country' => 'UK',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(
                ['name' => $supplier['name']],
                $supplier
            );
        }
    }
}
