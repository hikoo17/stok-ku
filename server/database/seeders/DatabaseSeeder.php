<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            UnitSeeder::class,
            UserSeeder::class,
        ]);

        // Demo products and transactions are never seeded in production.
        if (! app()->environment('production')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
