<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Crypto\AuthDemoService;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $user = User::where('username', 'nicolas.garcia')->first();

        if ($user === null || $user->cryptoDemoEntries()->exists()) {
            return;
        }

        $demo = app(AuthDemoService::class);

        $demo->create($user, [
            'username' => 'ana.estudiante',
            'password' => 'ClaveDemo#2026',
            'sym_algorithm' => 'aes-256-gcm',
            'asym_algorithm' => 'rsa-oaep-2048',
            'swap' => false,
        ]);

        $demo->create($user, [
            'username' => 'usuario.invertido',
            'password' => 'OtraClave#2026',
            'sym_algorithm' => 'chacha20-poly1305',
            'asym_algorithm' => 'sealed-box',
            'swap' => true,
        ]);
    }
}
