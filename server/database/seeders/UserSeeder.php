<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('app.admin.email', 'admin@stokku.test');
        $password = config('app.admin.password');

        if (app()->environment('production') && blank($password)) {
            $this->command?->warn(
                'UserSeeder dilewati: set ADMIN_EMAIL dan ADMIN_PASSWORD di .env produksi untuk membuat akun admin.'
            );

            return;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => config('app.admin.name', 'Administrator'),
                'password' => $password ?: 'password',
            ],
        );
    }
}
