<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients =[
          [
              'name' => 'Tradetek',
              'address' => 'Portao',
              'city' => 'Curitiba',
              'Country' => 'Brazil',
          ],
            [
                'name' => 'Argia',
                'address' => 'Passo largo',
                'city' => 'Curitiba',
                'Country' => 'Brazil'
            ]
        ];

        foreach ($clients as $client) {
            Client::updateOrCreate(
                ['name' => $client['name']],
                $client
            );
        }


    }
}
