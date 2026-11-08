<?php

namespace Tests\Feature\Seeders;

use App\Enums\UserRole;
use App\Models\CryptoDemoEntry;
use App\Models\User;
use App\Services\Crypto\BlindIndexService;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DefaultUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seed_creates_admin_and_nicolas_garcia(): void
    {
        $this->seed();

        $this->assertSame(2, User::count());
        $this->assertDatabaseHas('users', ['username' => 'admin', 'role' => 'admin']);
        $this->assertDatabaseHas('users', ['username' => 'nicolas.garcia', 'role' => 'user']);

        $nicolas = User::where('username', 'nicolas.garcia')->firstOrFail();

        $this->assertSame('Nicolás García Ramírez', $nicolas->name);
        $this->assertSame('nicolas.garcia@areandina.edu.co', $nicolas->email);
        $this->assertSame(UserRole::User, $nicolas->role);
        $this->assertTrue($nicolas->hasVerifiedEmail());
        $this->assertTrue(Hash::check('Nicolas1234!', $nicolas->password));

        $raw = DB::table('users')->where('id', $nicolas->id)->first();
        $this->assertNotSame('nicolas.garcia@areandina.edu.co', $raw->email);
        $this->assertSame(
            app(BlindIndexService::class)->hash('nicolas.garcia@areandina.edu.co'),
            $raw->email_hash,
        );
        $this->assertStringStartsWith('$argon2id$', $raw->password);
    }

    public function test_demo_data_is_seeded_for_nicolas_garcia(): void
    {
        $this->seed();

        $nicolas = User::where('username', 'nicolas.garcia')->firstOrFail();

        $this->assertSame(2, $nicolas->cryptoDemoEntries()->count());
        $this->assertSame(2, $nicolas->cryptoKeypairs()->count());
    }

    public function test_default_seeding_is_idempotent(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(2, User::count());
        $this->assertSame(2, CryptoDemoEntry::count());
    }

    public function test_legacy_demo_user_is_renamed_preserving_its_entries(): void
    {
        $legacy = User::factory()->create([
            'username' => 'demo',
            'email' => 'demo@criptolab.test',
        ]);

        $legacy->cryptoDemoEntries()->create([
            'username_plain' => 'usuario',
            'username_encrypted' => '{"version":1,"algorithm":"aes-256-gcm","ciphertext":"x"}',
            'password_plain' => 'ClaveDemo#2026',
            'password_encrypted' => '{"version":1,"algorithm":"rsa-oaep-2048","ciphertext":"x"}',
            'sym_algorithm' => 'aes-256-gcm',
            'asym_algorithm' => 'rsa-oaep-2048',
            'meta' => [],
        ]);

        $this->seed(DemoUserSeeder::class);

        $renamed = $legacy->fresh();

        $this->assertSame('nicolas.garcia', $renamed->username);
        $this->assertSame('Nicolás García Ramírez', $renamed->name);
        $this->assertSame('nicolas.garcia@areandina.edu.co', $renamed->email);
        $this->assertTrue(Hash::check('Nicolas1234!', $renamed->password));
        $this->assertSame(1, $renamed->cryptoDemoEntries()->count());
        $this->assertDatabaseMissing('users', ['username' => 'demo']);
    }
}
