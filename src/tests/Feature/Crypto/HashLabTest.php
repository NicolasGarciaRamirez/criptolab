<?php

namespace Tests\Feature\Crypto;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HashLabTest extends TestCase
{
    use RefreshDatabase;

    public function test_digest_endpoint_returns_known_sha256(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('crypto.hash.digest'), [
            'algorithm' => 'sha256',
            'text' => 'abc',
        ])->assertOk()
            ->assertJsonPath('result.hex', 'ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad');
    }

    public function test_hmac_endpoint_can_verify_an_expected_value(): void
    {
        $user = User::factory()->create();

        $result = $this->actingAs($user)->postJson(route('crypto.hash.hmac'), [
            'text' => 'Hi There',
            'key' => str_repeat('k', 20),
        ])->assertOk();

        $this->actingAs($user)->postJson(route('crypto.hash.hmac'), [
            'text' => 'Hi There',
            'key' => str_repeat('k', 20),
            'expected' => $result->json('result.hex'),
        ])->assertOk()->assertJsonPath('valid', true);
    }

    public function test_password_hash_and_verify_endpoints(): void
    {
        $user = User::factory()->create();

        $hash = $this->actingAs($user)->postJson(route('crypto.hash.password'), [
            'algorithm' => 'argon2id',
            'password' => 'Password123!',
        ])->assertOk()->json('result.hash');

        $this->assertStringStartsWith('$argon2id$', $hash);

        $this->actingAs($user)->postJson(route('crypto.hash.password.verify'), [
            'password' => 'Password123!',
            'hash' => $hash,
        ])->assertOk()->assertJsonPath('valid', true);

        $this->actingAs($user)->postJson(route('crypto.hash.password.verify'), [
            'password' => 'incorrecta',
            'hash' => $hash,
        ])->assertOk()->assertJsonPath('valid', false);
    }

    public function test_invalid_hash_algorithm_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('crypto.hash.digest'), [
            'algorithm' => 'md5-inventado',
            'text' => 'abc',
        ])->assertStatus(422)->assertJsonValidationErrors('algorithm');
    }
}
