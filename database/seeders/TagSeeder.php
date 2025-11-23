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
        // Maximum 5 tags for development
        $tags = [
            ['name' => 'Flood Light'],
            ['name' => 'Street Light'],
            ['name' => 'Solar Light'],
            ['name' => 'Christmas'],
            ['name' => 'Strips'],
        ];

        foreach ($tags as $tag) {
            Tag::updateOrCreate(
                ['name' => $tag['name']],
                $tag
            );
        }
    }
}
