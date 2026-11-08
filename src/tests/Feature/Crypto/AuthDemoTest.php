<?php

namespace Tests\Feature\Crypto;

use App\Models\AuditLog;
use App\Models\CryptoDemoEntry;
use App\Models\CryptoKeypair;
use App\Models\CryptoOperation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_entry_is_encrypted_at_rest_but_visible_in_the_table(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('crypto.auth-demo.store'), [
            'username' => 'ana.estudiante',
            'password' => 'ClaveDemo#2026',
            'sym_algorithm' => 'aes-256-gcm',
            'asym_algorithm' => 'rsa-oaep-2048',
        ]);

        $response->assertRedirect(route('crypto.auth-demo'));
        $entry = CryptoDemoEntry::firstOrFail();
        $raw = DB::table('crypto_demo_entries')->where('id', $entry->id)->first();

        $this->assertNotSame('ana.estudiante', $raw->username_plain);
        $this->assertNotSame('ClaveDemo#2026', $raw->password_plain);
        $this->assertStringContainsString('aes-256-gcm', $raw->username_encrypted);
        $this->assertStringContainsString('rsa-oaep-2048', $raw->password_encrypted);

        $this->actingAs($user)->get(route('crypto.auth-demo'))
            ->assertOk()
            ->assertSee('ana.estudiante')
            ->assertSee('ClaveDemo#2026')
            ->assertSee('esta pantalla es solo educativa');

        $this->assertDatabaseHas('crypto_operations', [
            'user_id' => $user->id,
            'type' => 'demo_entry',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'demo_entry_created',
        ]);
    }

    public function test_entry_can_be_decrypted_by_its_owner(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('crypto.auth-demo.store'), [
            'username' => 'ana.estudiante',
            'password' => 'ClaveDemo#2026',
            'sym_algorithm' => 'aes-256-gcm',
            'asym_algorithm' => 'rsa-oaep-2048',
        ]);

        $entry = CryptoDemoEntry::firstOrFail();

        $this->actingAs($user)->postJson(route('crypto.auth-demo.decrypt', $entry->id))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('username', 'ana.estudiante')
            ->assertJsonPath('password', 'ClaveDemo#2026');

        $this->assertDatabaseHas('crypto_operations', [
            'user_id' => $user->id,
            'type' => 'decrypt',
            'context' => 'auth-demo',
        ]);
    }

    public function test_swapped_families_still_roundtrip(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('crypto.auth-demo.store'), [
            'username' => 'usuario.invertido',
            'password' => 'OtraClave#2026',
            'sym_algorithm' => 'chacha20-poly1305',
            'asym_algorithm' => 'sealed-box',
            'swap' => true,
        ])->assertRedirect(route('crypto.auth-demo'));

        $entry = CryptoDemoEntry::firstOrFail();
        $this->assertTrue($entry->meta['swap']);
        $this->assertStringContainsString('sealed-box', $entry->username_encrypted);
        $this->assertStringContainsString('chacha20-poly1305', $entry->password_encrypted);

        $this->actingAs($user)->postJson(route('crypto.auth-demo.decrypt', $entry->id))
            ->assertOk()
            ->assertJsonPath('username', 'usuario.invertido')
            ->assertJsonPath('password', 'OtraClave#2026');
    }

    public function test_other_users_cannot_decrypt_or_delete_the_entry(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($owner)->post(route('crypto.auth-demo.store'), [
            'username' => 'ana',
            'password' => 'ClaveDemo#2026',
            'sym_algorithm' => 'aes-256-gcm',
            'asym_algorithm' => 'rsa-oaep-2048',
        ]);

        $entry = CryptoDemoEntry::firstOrFail();

        $this->actingAs($other)->postJson(route('crypto.auth-demo.decrypt', $entry->id))
            ->assertNotFound();

        $this->actingAs($other)->delete(route('crypto.auth-demo.destroy', $entry->id))
            ->assertNotFound();

        $this->assertNotNull($entry->fresh());
    }

    public function test_keypair_is_saved_encrypted_for_the_demo(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('crypto.auth-demo.store'), [
            'username' => 'ana',
            'password' => 'ClaveDemo#2026',
            'sym_algorithm' => 'aes-256-gcm',
            'asym_algorithm' => 'rsa-oaep-2048',
        ]);

        $keypair = CryptoKeypair::firstOrFail();
        $raw = DB::table('crypto_keypairs')->where('id', $keypair->id)->first();

        $this->assertStringNotContainsString('PRIVATE KEY', $raw->private_key_encrypted);
        $this->assertStringStartsWith('v1:', $raw->private_key_encrypted);

        $entry = CryptoDemoEntry::firstOrFail();
        $this->assertNotNull($entry->meta['symmetric_key_encrypted']);
        $this->assertStringStartsWith('v1:', $entry->meta['symmetric_key_encrypted']);
    }

    public function test_export_returns_plaintext_and_ciphertext_as_json(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('crypto.auth-demo.store'), [
            'username' => 'ana',
            'password' => 'ClaveDemo#2026',
            'sym_algorithm' => 'aes-256-gcm',
            'asym_algorithm' => 'rsa-oaep-2048',
        ]);

        $response = $this->actingAs($user)->get(route('crypto.auth-demo.export'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/json; charset=UTF-8');

        $this->assertSame('ana', $response->json('entries.0.username_plain'));
        $this->assertNotEmpty($response->json('entries.0.username_encrypted'));
    }

    public function test_tampered_ciphertext_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('crypto.auth-demo.store'), [
            'username' => 'ana',
            'password' => 'ClaveDemo#2026',
            'sym_algorithm' => 'aes-256-gcm',
            'asym_algorithm' => 'rsa-oaep-2048',
        ]);

        $entry = CryptoDemoEntry::firstOrFail();
        $envelope = json_decode($entry->password_encrypted, true);
        $raw = base64_decode($envelope['ciphertext'], true);
        $raw[0] = chr(ord($raw[0]) ^ 0x01);
        $envelope['ciphertext'] = base64_encode($raw);
        $entry->forceFill(['password_encrypted' => json_encode($envelope)])->save();

        $this->actingAs($user)->postJson(route('crypto.auth-demo.decrypt', $entry->id))
            ->assertStatus(422)
            ->assertJsonPath('ok', false);
    }

    public function test_plaintext_is_not_written_to_audit_or_operation_logs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('crypto.auth-demo.store'), [
            'username' => 'usuario-secreto',
            'password' => 'ClaveUltraSecreta#2026',
            'sym_algorithm' => 'aes-256-gcm',
            'asym_algorithm' => 'rsa-oaep-2048',
        ]);

        $audit = json_encode(AuditLog::all()->toArray());
        $operations = json_encode(CryptoOperation::all()->toArray());

        $this->assertFalse(Str::contains($audit, 'usuario-secreto'));
        $this->assertFalse(Str::contains($audit, 'ClaveUltraSecreta#2026'));
        $this->assertFalse(Str::contains($operations, 'usuario-secreto'));
        $this->assertFalse(Str::contains($operations, 'ClaveUltraSecreta#2026'));
    }
}
