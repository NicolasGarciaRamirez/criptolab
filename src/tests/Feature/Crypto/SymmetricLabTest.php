<?php

namespace Tests\Feature\Crypto;

use App\Models\CryptoOperation;
use App\Models\User;
use App\Services\Crypto\Registries\SymmetricCipherRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SymmetricLabTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_the_lab(): void
    {
        $this->get('/lab/simetrico')->assertRedirect('/login');
    }

    public function test_keygen_encrypt_and_decrypt_roundtrip(): void
    {
        $user = User::factory()->create();

        $keyResponse = $this->actingAs($user)->postJson(route('crypto.symmetric.key'), [
            'algorithm' => 'aes-256-gcm',
        ]);

        $keyResponse->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('algorithm', 'aes-256-gcm');

        $key = $keyResponse->json('key');
        $this->assertSame(32, strlen(base64_decode($key, true)));

        $encrypt = $this->actingAs($user)->postJson(route('crypto.symmetric.encrypt'), [
            'algorithm' => 'aes-256-gcm',
            'plaintext' => 'mensaje confidencial',
            'key' => $key,
        ]);

        $encrypt->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('envelope.algorithm', 'aes-256-gcm');

        $payload = $encrypt->json('envelope_json');

        $this->actingAs($user)->postJson(route('crypto.symmetric.decrypt'), [
            'algorithm' => 'aes-256-gcm',
            'key' => $key,
            'payload' => $payload,
        ])->assertOk()->assertJsonPath('plaintext', 'mensaje confidencial');

        $this->assertDatabaseHas('crypto_operations', [
            'user_id' => $user->id,
            'type' => 'encrypt',
            'algorithm' => 'aes-256-gcm',
        ]);
        $this->assertDatabaseHas('crypto_operations', [
            'user_id' => $user->id,
            'type' => 'decrypt',
            'algorithm' => 'aes-256-gcm',
        ]);
    }

    public function test_tampered_gcm_payload_returns_422(): void
    {
        $user = User::factory()->create();
        $key = app(SymmetricCipherRegistry::class)
            ->get('aes-256-gcm')->generateKey();

        $encrypt = $this->actingAs($user)->postJson(route('crypto.symmetric.encrypt'), [
            'algorithm' => 'aes-256-gcm',
            'plaintext' => 'texto',
            'key' => $key,
        ]);

        $envelope = $encrypt->json('envelope');
        $raw = base64_decode($envelope['ciphertext'], true);
        $raw[0] = chr(ord($raw[0]) ^ 0x01);
        $envelope['ciphertext'] = base64_encode($raw);

        $this->actingAs($user)->postJson(route('crypto.symmetric.decrypt'), [
            'algorithm' => 'aes-256-gcm',
            'key' => $key,
            'payload' => json_encode($envelope),
        ])->assertStatus(422)->assertJsonPath('ok', false);
    }

    public function test_iv_demo_shows_different_values(): void
    {
        $user = User::factory()->create();
        $key = app(SymmetricCipherRegistry::class)
            ->get('aes-256-gcm')->generateKey();

        $response = $this->actingAs($user)->postJson(route('crypto.symmetric.iv'), [
            'algorithm' => 'aes-256-gcm',
            'plaintext' => 'mismo texto',
            'key' => $key,
        ]);

        $response->assertOk()
            ->assertJsonPath('different_iv', true)
            ->assertJsonPath('different_ciphertext', true);
    }

    public function test_key_derivation_returns_salt_and_key(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('crypto.symmetric.derive'), [
            'method' => 'pbkdf2',
            'passphrase' => 'frase de prueba',
            'iterations' => 1000,
            'bytes' => 32,
        ]);

        $response->assertOk()
            ->assertJsonPath('derivation.algorithm', 'pbkdf2-sha256')
            ->assertJsonPath('derivation.bytes', 32);

        $this->assertNotEmpty($response->json('derivation.salt'));
        $this->assertSame(32, strlen(base64_decode($response->json('derivation.key'), true)));
    }

    public function test_unknown_algorithm_is_rejected_by_validation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('crypto.symmetric.key'), [
            'algorithm' => 'algoritmo-inventado',
        ])->assertStatus(422)->assertJsonValidationErrors('algorithm');
    }

    public function test_operations_do_not_store_plaintext_or_keys(): void
    {
        $user = User::factory()->create();
        $key = app(SymmetricCipherRegistry::class)
            ->get('aes-256-gcm')->generateKey();

        $this->actingAs($user)->postJson(route('crypto.symmetric.encrypt'), [
            'algorithm' => 'aes-256-gcm',
            'plaintext' => 'secreto-que-no-debe-registrarse',
            'key' => $key,
        ]);

        $operation = CryptoOperation::firstOrFail();
        $serialized = json_encode($operation->toArray());

        $this->assertFalse(Str::contains($serialized, 'secreto-que-no-debe-registrarse'));
        $this->assertFalse(Str::contains($serialized, $key));
    }
}
