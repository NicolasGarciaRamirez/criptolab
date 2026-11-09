<?php

namespace Tests\Feature\Crypto;

use App\Models\User;
use App\Services\Crypto\Registries\SymmetricCipherRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FileCryptoLabTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_can_be_encrypted_and_decrypted_over_http(): void
    {
        $user = User::factory()->create();
        $key = app(SymmetricCipherRegistry::class)->get('aes-256-gcm')->generateKey();
        $file = UploadedFile::fake()->createWithContent('documento.txt', 'contenido visible');

        $encrypted = $this->actingAs($user)->post(route('crypto.symmetric.files.encrypt'), [
            'file' => $file,
            'algorithm' => 'aes-256-gcm',
            'key' => $key,
        ]);

        $encrypted->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('original_name', 'documento.txt');

        $cryptFile = UploadedFile::fake()->createWithContent('documento.txt.crypt', $encrypted->json('envelope_json'));

        $this->actingAs($user)->post(route('crypto.symmetric.files.decrypt'), [
            'file' => $cryptFile,
            'algorithm' => 'aes-256-gcm',
            'key' => $key,
        ])->assertOk()
            ->assertJsonPath('name', 'documento.txt')
            ->assertJsonPath('bytes', strlen('contenido visible'));
    }

    public function test_tampered_crypt_file_returns_422(): void
    {
        $user = User::factory()->create();
        $key = app(SymmetricCipherRegistry::class)->get('aes-256-gcm')->generateKey();
        $file = UploadedFile::fake()->createWithContent('documento.txt', 'contenido visible');

        $encrypted = $this->actingAs($user)->post(route('crypto.symmetric.files.encrypt'), [
            'file' => $file,
            'algorithm' => 'aes-256-gcm',
            'key' => $key,
        ])->assertOk();

        $envelope = $encrypted->json('envelope');
        $raw = base64_decode($envelope['ciphertext'], true);
        $raw[0] = chr(ord($raw[0]) ^ 0x01);
        $envelope['ciphertext'] = base64_encode($raw);

        $cryptFile = UploadedFile::fake()->createWithContent('alterado.crypt', json_encode($envelope));

        $this->actingAs($user)->post(route('crypto.symmetric.files.decrypt'), [
            'file' => $cryptFile,
            'algorithm' => 'aes-256-gcm',
            'key' => $key,
        ])->assertStatus(422);
    }
}
