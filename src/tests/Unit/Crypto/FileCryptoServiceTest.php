<?php

namespace Tests\Unit\Crypto;

use App\Exceptions\CryptoException;
use App\Services\Crypto\Dto\CryptoEnvelope;
use App\Services\Crypto\FileCryptoService;
use App\Services\Crypto\Registries\SymmetricCipherRegistry;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FileCryptoServiceTest extends TestCase
{
    public function test_file_roundtrip_preserves_name_mime_and_content(): void
    {
        $service = app(FileCryptoService::class);
        $cipher = app(SymmetricCipherRegistry::class)->get('aes-256-gcm');
        $key = $cipher->generateKey();
        $file = UploadedFile::fake()->createWithContent('nota.txt', 'contenido del archivo secreto');

        $encrypted = $service->encrypt($file, 'aes-256-gcm', $key);
        $restored = $service->decrypt($encrypted['envelope'], 'aes-256-gcm', $key);

        $this->assertSame('nota.txt', $restored['name']);
        $this->assertSame('contenido del archivo secreto', $restored['contents']);
    }

    public function test_file_encryption_rejects_tampered_ciphertext(): void
    {
        $service = app(FileCryptoService::class);
        $cipher = app(SymmetricCipherRegistry::class)->get('aes-256-gcm');
        $key = $cipher->generateKey();
        $file = UploadedFile::fake()->createWithContent('nota.txt', 'contenido');
        $encrypted = $service->encrypt($file, 'aes-256-gcm', $key);

        $envelope = $encrypted['envelope']->toArray();
        $raw = base64_decode($envelope['ciphertext'], true);
        $raw[0] = chr(ord($raw[0]) ^ 0x01);
        $envelope['ciphertext'] = base64_encode($raw);

        $this->expectException(CryptoException::class);
        $service->decrypt(CryptoEnvelope::fromArray($envelope), 'aes-256-gcm', $key);
    }

    public function test_file_encryption_rejects_unauthenticated_ciphers(): void
    {
        $service = app(FileCryptoService::class);
        $file = UploadedFile::fake()->createWithContent('nota.txt', 'contenido');

        $this->expectException(CryptoException::class);
        $service->encrypt($file, 'aes-256-cbc', app(SymmetricCipherRegistry::class)->get('aes-256-cbc')->generateKey());
    }
}
