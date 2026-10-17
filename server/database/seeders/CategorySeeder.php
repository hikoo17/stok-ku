<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Radiator Hose',
            'L-Shaped Hose',
            'Straight Hose / Long Length',
            'Turbo',
            'Bypass',
            'Blower',
            'Fuel Tank',
            'Vacuum',
            'Oil Cooler',
        ];

        foreach ($categories as $name) {
            Category::query()->updateOrCreate(
                ['name' => $name],
                [
                    'slug' => Str::slug($name),
                    'is_active' => true,
                ],
            );
        }
    }
}
