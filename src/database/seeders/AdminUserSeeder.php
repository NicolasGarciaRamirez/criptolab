<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrador CriptoLab',
                'email' => 'admin@criptolab.test',
                'password' => 'Admin1234!',
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ],
        );
    }
}
