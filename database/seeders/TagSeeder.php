<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            ['name' => 'Flood Light'],
            ['name' => 'Street Light'],
            ['name' => 'Solar Light'],
            ['name' => 'Christmas'],
            ['name' => 'Strips'],
            ['name' => 'Drivers'],
            ['name' => 'LED Chip'],
        ];

        foreach ($tags as $tag) {
            Tag::updateOrCreate(
                ['name' => $tag['name']],
                $tag
            );
        }
    }
}
