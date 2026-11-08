<?php

namespace Tests\Feature\Crypto;

use App\Models\CryptoKeypair;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AsymmetricLabTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_encrypt_and_decrypt_with_rsa(): void
    {
        $user = User::factory()->create();

        $keys = $this->actingAs($user)->postJson(route('crypto.asymmetric.keypair'), [
            'algorithm' => 'rsa-oaep-2048',
            'save' => false,
        ]);

        $keys->assertOk()->assertJsonPath('ok', true);
        $this->assertStringContainsString('PUBLIC KEY', $keys->json('public_key'));
        $this->assertStringContainsString('PRIVATE KEY', $keys->json('private_key'));

        $encrypted = $this->actingAs($user)->postJson(route('crypto.asymmetric.encrypt'), [
            'algorithm' => 'rsa-oaep-2048',
            'plaintext' => 'contraseña de demostración',
            'public_key' => $keys->json('public_key'),
        ])->assertOk();

        $this->actingAs($user)->postJson(route('crypto.asymmetric.decrypt'), [
            'algorithm' => 'rsa-oaep-2048',
            'payload' => $encrypted->json('envelope_json'),
            'private_key' => $keys->json('private_key'),
        ])->assertOk()->assertJsonPath('plaintext', 'contraseña de demostración');
    }

    public function test_saved_private_key_is_encrypted_at_rest_and_never_returned_in_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('crypto.asymmetric.keypair'), [
            'algorithm' => 'rsa-oaep-2048',
            'name' => 'Llave de prueba',
            'save' => true,
        ])->assertOk();

        $keypair = CryptoKeypair::firstOrFail();
        $raw = DB::table('crypto_keypairs')->where('id', $keypair->id)->first();

        $this->assertStringNotContainsString('PRIVATE KEY', $raw->private_key_encrypted);
        $this->assertStringContainsString('PUBLIC KEY', $raw->public_key);
        $this->assertSame($response->json('fingerprint'), $keypair->fingerprint);
        $this->assertStringContainsString('PRIVATE KEY', $keypair->private_key_encrypted);

        $this->actingAs($user)->get(route('crypto.keypairs.index'))
            ->assertOk()
            ->assertDontSee('PRIVATE KEY');
    }

    public function test_sign_and_verify_with_tamper_detection(): void
    {
        $user = User::factory()->create();

        $keys = $this->actingAs($user)->postJson(route('crypto.asymmetric.signer.keypair'), [
            'algorithm' => 'ed25519',
            'save' => false,
        ])->assertOk();

        $signature = $this->actingAs($user)->postJson(route('crypto.asymmetric.sign'), [
            'algorithm' => 'ed25519',
            'message' => 'Documento original',
            'private_key' => $keys->json('private_key'),
        ])->assertOk()->json('signature');

        $this->actingAs($user)->postJson(route('crypto.asymmetric.verify'), [
            'algorithm' => 'ed25519',
            'message' => 'Documento original',
            'signature' => $signature,
            'public_key' => $keys->json('public_key'),
        ])->assertOk()->assertJsonPath('valid', true);

        $this->actingAs($user)->postJson(route('crypto.asymmetric.verify'), [
            'algorithm' => 'ed25519',
            'message' => 'Documento alterado',
            'signature' => $signature,
            'public_key' => $keys->json('public_key'),
        ])->assertOk()->assertJsonPath('valid', false);
    }

    public function test_hybrid_encryption_handles_long_messages(): void
    {
        $user = User::factory()->create();

        $keys = $this->actingAs($user)->postJson(route('crypto.asymmetric.keypair'), [
            'algorithm' => 'hybrid-rsa-aes-256-gcm',
            'save' => false,
        ])->assertOk();

        $message = str_repeat('Texto largo. ', 30);

        $encrypted = $this->actingAs($user)->postJson(route('crypto.asymmetric.encrypt'), [
            'algorithm' => 'hybrid-rsa-aes-256-gcm',
            'plaintext' => $message,
            'public_key' => $keys->json('public_key'),
        ])->assertOk();

        $this->actingAs($user)->postJson(route('crypto.asymmetric.decrypt'), [
            'algorithm' => 'hybrid-rsa-aes-256-gcm',
            'payload' => $encrypted->json('envelope_json'),
            'private_key' => $keys->json('private_key'),
        ])->assertOk()->assertJsonPath('plaintext', $message);
    }

    public function test_public_key_download_is_limited_to_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $keys = $this->actingAs($owner)->postJson(route('crypto.asymmetric.keypair'), [
            'algorithm' => 'rsa-oaep-2048',
            'save' => true,
        ])->assertOk();

        $id = $keys->json('keypair_id');

        $this->actingAs($owner)->get(route('crypto.keypairs.public', $id))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $this->actingAs($other)->get(route('crypto.keypairs.public', $id))->assertNotFound();
    }
}
