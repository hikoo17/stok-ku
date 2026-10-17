<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Piece', 'abbreviation' => 'pcs'],
            ['name' => 'Set', 'abbreviation' => 'set'],
            ['name' => 'Meter', 'abbreviation' => 'm'],
            ['name' => 'Roll', 'abbreviation' => 'roll'],
            ['name' => 'Length', 'abbreviation' => 'length'],
        ];

        foreach ($units as $unit) {
            Unit::query()->updateOrCreate(
                ['abbreviation' => $unit['abbreviation']],
                ['name' => $unit['name'], 'is_active' => true],
            );
        }
    }
}
