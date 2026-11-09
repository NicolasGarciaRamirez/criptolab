<?php

namespace Tests\Feature\Crypto;

use App\Models\User;
use App\Services\Crypto\Asymmetric\RsaOaep;
use App\Services\Crypto\QuizService;
use App\Services\Crypto\Registries\SymmetricCipherRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedCryptoEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_aes_modes_and_nonce_reuse_endpoints(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('crypto.symmetric.modes.compare'), [
            'plaintext' => str_repeat('BLOQUE-REPETIDO!', 4),
        ])->assertOk()->assertJsonPath('ok', true);

        $this->actingAs($user)->postJson(route('crypto.symmetric.modes.nonce'), [
            'algorithm' => 'aes-256-gcm',
            'first' => 'texto uno',
            'second' => 'texto dos',
        ])->assertOk()->assertJsonPath('result.leak_matches', true);
    }

    public function test_aad_endpoint_requires_the_same_aad(): void
    {
        $user = User::factory()->create();
        $key = app(SymmetricCipherRegistry::class)->get('aes-256-gcm')->generateKey();

        $encrypted = $this->actingAs($user)->postJson(route('crypto.symmetric.aad.encrypt'), [
            'plaintext' => 'mensaje',
            'key' => $key,
            'aad' => 'usuario=42',
        ])->assertOk();

        $this->actingAs($user)->postJson(route('crypto.symmetric.aad.decrypt'), [
            'payload' => $encrypted->json('envelope_json'),
            'key' => $key,
            'aad' => 'usuario=43',
        ])->assertStatus(422);
    }

    public function test_x25519_endpoints_return_the_same_secret(): void
    {
        $user = User::factory()->create();
        $pairs = $this->actingAs($user)->postJson(route('crypto.asymmetric.x25519.generate'))->assertOk();

        $response = $this->actingAs($user)->postJson(route('crypto.asymmetric.x25519.shared'), [
            'alice_private_key' => $pairs->json('alice.private_key'),
            'alice_public_key' => $pairs->json('alice.public_key'),
            'bob_private_key' => $pairs->json('bob.private_key'),
            'bob_public_key' => $pairs->json('bob.public_key'),
            'message' => 'mensaje x25519',
        ]);

        $response->assertOk()
            ->assertJsonPath('same_secret', true)
            ->assertJsonPath('same_key', true)
            ->assertJsonPath('decrypted', 'mensaje x25519');
    }

    public function test_sign_encrypt_endpoints(): void
    {
        $user = User::factory()->create();
        $pairs = $this->actingAs($user)->postJson(route('crypto.asymmetric.sign-encrypt.keys'))->assertOk();

        $encrypted = $this->actingAs($user)->postJson(route('crypto.asymmetric.sign-encrypt.encrypt'), [
            'message' => 'mensaje firmado y cifrado',
            'signer_algorithm' => $pairs->json('signing.algorithm'),
            'cipher_algorithm' => $pairs->json('encryption.algorithm'),
            'signing_private_key' => $pairs->json('signing.private_key'),
            'signing_public_key' => $pairs->json('signing.public_key'),
            'encryption_public_key' => $pairs->json('encryption.public_key'),
        ])->assertOk();

        $this->actingAs($user)->postJson(route('crypto.asymmetric.sign-encrypt.decrypt'), [
            'payload' => $encrypted->json('envelope_json'),
            'cipher_algorithm' => $pairs->json('encryption.algorithm'),
            'encryption_private_key' => $pairs->json('encryption.private_key'),
        ])->assertOk()
            ->assertJsonPath('message', 'mensaje firmado y cifrado')
            ->assertJsonPath('signature_valid', true);
    }

    public function test_certificate_and_key_import_endpoints(): void
    {
        $user = User::factory()->create();

        $certificate = $this->actingAs($user)->postJson(route('crypto.asymmetric.certificates.generate'), [
            'common_name' => 'criptolab.test',
            'organization' => 'CriptoLab',
            'country' => 'CO',
            'days' => 30,
        ])->assertOk()->assertJsonPath('verified', true);

        $this->actingAs($user)->postJson(route('crypto.asymmetric.certificates.inspect'), [
            'certificate' => $certificate->json('certificate'),
        ])->assertOk()->assertJsonPath('verified', true);

        $pair = (new RsaOaep(2048))->generateKeyPair();

        $this->actingAs($user)->postJson(route('crypto.asymmetric.import.inspect'), [
            'public_key' => $pair->publicKey,
            'private_key' => $pair->privateKey,
            'algorithm' => 'rsa-oaep-2048',
            'name' => 'Importada',
            'save' => true,
        ])->assertOk()->assertJsonPath('saved', true);

        $this->assertDatabaseHas('crypto_keypairs', ['name' => 'Importada', 'algorithm' => 'rsa-oaep-2048']);
    }

    public function test_timing_rainbow_benchmark_and_quiz_endpoints(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('crypto.hash.timing.compare'), [
            'first' => 'abcdef',
            'second' => 'abcdef',
            'iterations' => 1000,
        ])->assertOk()->assertJsonPath('result.same', true);

        $this->actingAs($user)->postJson(route('crypto.hash.rainbow.consult'), [
            'password' => 'admin',
        ])->assertOk()->assertJsonPath('lookup.found', true);

        $this->actingAs($user)->postJson(route('crypto.benchmark.run'))
            ->assertOk()
            ->assertJsonCount(17, 'rows');

        $questions = app(QuizService::class)->questions('hash');
        $answers = [];
        foreach ($questions as $question) {
            $answers[$question['id']] = 'a';
        }

        $this->actingAs($user)->postJson(route('crypto.quiz.evaluate'), [
            'module' => 'hash',
            'answers' => $answers,
        ])->assertOk()->assertJsonPath('result.total', 5);
    }
}
