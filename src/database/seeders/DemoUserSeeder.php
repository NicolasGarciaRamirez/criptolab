<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        // Migra la cuenta demo anterior conservando sus entradas y llaves.
        $legacy = User::where('username', 'demo')->first();

        if ($legacy !== null && User::where('username', 'nicolas.garcia')->doesntExist()) {
            $legacy->fill([
                'name' => 'Nicolás García Ramírez',
                'username' => 'nicolas.garcia',
                'email' => 'nicolas.garcia@areandina.edu.co',
                'password' => 'Nicolas1234!',
                'role' => UserRole::User,
                'email_verified_at' => now(),
            ])->save();

            return;
        }

        User::firstOrCreate(
            ['username' => 'nicolas.garcia'],
            [
                'name' => 'Nicolás García Ramírez',
                'email' => 'nicolas.garcia@areandina.edu.co',
                'password' => 'Nicolas1234!',
                'role' => UserRole::User,
                'email_verified_at' => now()
            ])->save();
        
    }
}
