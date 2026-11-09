<?php

namespace Tests\Unit\Crypto;

use App\Services\Crypto\BenchmarkService;
use App\Services\Crypto\CryptoOverheadService;
use App\Services\Crypto\RainbowTableService;
use App\Services\Crypto\TimingComparisonService;
use Tests\TestCase;

class LabUtilitiesTest extends TestCase
{
    public function test_timing_comparison_returns_metrics(): void
    {
        $result = app(TimingComparisonService::class)->compare('abcdef', 'abcdef', 1000);

        $this->assertSame(1000, $result['iterations']);
        $this->assertTrue($result['same']);
        $this->assertGreaterThanOrEqual(0, $result['standard_ms']);
        $this->assertGreaterThanOrEqual(0, $result['hash_equals_ms']);
    }

    public function test_rainbow_table_finds_common_password_and_argon_hash(): void
    {
        $service = app(RainbowTableService::class);
        $lookup = $service->lookup('admin');
        $argon = $service->argon2id('admin');

        $this->assertTrue($lookup['found']);
        $this->assertSame(4, $lookup['position']);
        $this->assertStringStartsWith('$argon2id$', $argon['hash']);
        $this->assertTrue($argon['different_each_time']);
    }

    public function test_benchmark_returns_rows(): void
    {
        $result = app(BenchmarkService::class)->run();

        $this->assertNotEmpty($result['rows']);
        $this->assertArrayHasKey('operations_per_second', $result['rows'][0]);
    }

    public function test_overhead_comparison_has_symmetric_and_asymmetric_rows(): void
    {
        $rows = app(CryptoOverheadService::class)->rows();
        $families = array_column($rows, 'family');

        $this->assertContains('Simétrico', $families);
        $this->assertContains('Asimétrico', $families);
        $this->assertContains('Firma', $families);
    }
}
