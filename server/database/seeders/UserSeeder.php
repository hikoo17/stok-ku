<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@stokku.test');
        $password = env('ADMIN_PASSWORD');

        if (app()->environment('production') && blank($password)) {
            $this->command?->warn(
                'UserSeeder dilewati: set ADMIN_EMAIL dan ADMIN_PASSWORD di .env produksi untuk membuat akun admin.'
            );

            return;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Administrator'),
                'password' => $password ?: 'password',
            ],
        );
    }
}
