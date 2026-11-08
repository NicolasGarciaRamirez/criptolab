<?php

namespace Tests\Unit\Crypto;

use App\Exceptions\CryptoException;
use App\Services\Crypto\Hash\HashService;
use App\Services\Crypto\Hash\PasswordHasher;
use App\Services\Crypto\KeyDerivationService;
use Tests\TestCase;

class HashAndDerivationTest extends TestCase
{
    public function test_sha256_known_vector(): void
    {
        $result = app(HashService::class)->digest('sha256', 'abc');

        $this->assertSame('ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad', $result['hex']);
    }

    public function test_all_digest_algorithms_produce_expected_lengths(): void
    {
        $service = app(HashService::class);

        foreach ($service->available() as $id => $config) {
            $result = $service->digest($id, 'CriptoLab');

            $this->assertSame($config['bytes'] * 2, strlen($result['hex']), $id);
        }
    }

    public function test_hmac_sha256_known_vector(): void
    {
        $key = str_repeat(chr(0x0B), 20);
        $result = app(HashService::class)->hmac('Hi There', $key);

        $this->assertSame('b0344c61d8db38535ca8afceaf0bf12b881dc200c9833da726e9376c2e32cff7', $result['hex']);
        $this->assertTrue(app(HashService::class)->verifyHmac('Hi There', $key, $result['hex']));
        $this->assertFalse(app(HashService::class)->verifyHmac('Hi There!', $key, $result['hex']));
    }

    public function test_password_hashes_include_their_own_salt(): void
    {
        $service = app(HashService::class);

        $bcryptOne = $service->passwordHash('bcrypt', 'Password123!');
        $bcryptTwo = $service->passwordHash('bcrypt', 'Password123!');
        $argonOne = $service->passwordHash('argon2id', 'Password123!');

        $this->assertNotSame($bcryptOne['hash'], $bcryptTwo['hash']);
        $this->assertTrue($service->verifyPassword('Password123!', $bcryptOne['hash']));
        $this->assertTrue($service->verifyPassword('Password123!', $argonOne['hash']));
        $this->assertFalse($service->verifyPassword('incorrecta', $argonOne['hash']));
    }

    public function test_password_hasher_service_verifies_both_algorithms(): void
    {
        $hasher = app(PasswordHasher::class);

        foreach ([$hasher->bcrypt('Secreta123!'), $hasher->argon2id('Secreta123!')] as $result) {
            $this->assertTrue($hasher->verify('Secreta123!', $result['hash']));
            $this->assertFalse($hasher->verify('otra', $result['hash']));
        }
    }

    public function test_pbkdf2_is_deterministic_with_the_same_salt(): void
    {
        $kdf = app(KeyDerivationService::class);
        $first = $kdf->pbkdf2('frase', null, 1_000, 32);
        $second = $kdf->pbkdf2('frase', $first['salt'], 1_000, 32);
        $different = $kdf->pbkdf2('frase', null, 1_000, 32);

        $this->assertSame($first['key'], $second['key']);
        $this->assertNotSame($first['key'], $different['key']);
        $this->assertSame(32, strlen(base64_decode($first['key'], true)));
    }

    public function test_argon2id_derivation_supports_16_and_32_byte_keys(): void
    {
        $kdf = app(KeyDerivationService::class);

        foreach ([16, 32] as $bytes) {
            $result = $kdf->argon2id('frase', null, $bytes);
            $this->assertSame($bytes, strlen(base64_decode($result['key'], true)));
            $this->assertSame('argon2id', $result['algorithm']);
        }
    }

    public function test_key_derivation_rejects_invalid_output_lengths(): void
    {
        $this->expectException(CryptoException::class);
        app(KeyDerivationService::class)->pbkdf2('frase', null, 1_000, 24);
    }
}
