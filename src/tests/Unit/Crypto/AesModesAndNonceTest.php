<?php

namespace Tests\Unit\Crypto;

use App\Exceptions\CryptoException;
use App\Services\Crypto\AesModesComparisonService;
use App\Services\Crypto\NonceReuseDemoService;
use Tests\TestCase;

class AesModesAndNonceTest extends TestCase
{
    public function test_modes_comparison_shows_ecb_patterns_and_gcm_tag(): void
    {
        $result = app(AesModesComparisonService::class)->compare(str_repeat('BLOQUE-REPETIDO!', 4));
        $modes = collect($result['modes'])->keyBy('id');

        $this->assertGreaterThan(0, $modes['aes-256-ecb']['repeated_blocks']);
        $this->assertGreaterThanOrEqual(0, $modes['aes-256-cbc']['repeated_blocks']);
        $this->assertNotEmpty($modes['aes-256-gcm']['tag_hex']);
        $this->assertTrue($modes['aes-256-gcm']['authenticated']);
        $this->assertFalse($modes['aes-256-ecb']['authenticated']);
    }

    public function test_nonce_reuse_exposes_plaintext_xor_for_gcm(): void
    {
        $result = app(NonceReuseDemoService::class)->demonstrate('aes-256-gcm', 'texto uno', 'texto dos');

        $this->assertTrue($result['leak_matches']);
        $this->assertSame($result['xor_ciphertexts_hex'], $result['xor_plaintexts_hex']);
        $this->assertNotEmpty($result['nonce_hex']);
    }

    public function test_nonce_reuse_requires_equal_lengths_and_supported_algorithm(): void
    {
        $service = app(NonceReuseDemoService::class);

        $this->expectException(CryptoException::class);
        $service->demonstrate('aes-256-gcm', 'corto', 'texto más largo');
    }
}
