<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Crypto\Asymmetric\EcdsaSigner;
use App\Services\Crypto\Asymmetric\Ed25519Signer;
use App\Services\Crypto\Asymmetric\HybridEncryption;
use App\Services\Crypto\Asymmetric\RsaOaep;
use App\Services\Crypto\Asymmetric\RsaSigner;
use App\Services\Crypto\Asymmetric\SealedBox;
use App\Services\Crypto\BlindIndexService;
use App\Services\Crypto\MasterKeyService;
use App\Services\Crypto\Registries\AsymmetricCipherRegistry;
use App\Services\Crypto\Registries\SignerRegistry;
use App\Services\Crypto\Registries\SymmetricCipherRegistry;
use App\Services\Crypto\Symmetric\Aes128Ctr;
use App\Services\Crypto\Symmetric\Aes256Cbc;
use App\Services\Crypto\Symmetric\Aes256Gcm;
use App\Services\Crypto\Symmetric\ChaCha20Poly1305;
use App\Services\Crypto\Symmetric\Xsalsa20Poly1305;
use Illuminate\Support\ServiceProvider;

class CryptoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MasterKeyService::class);
        $this->app->singleton(BlindIndexService::class);

        $this->app->singleton(SymmetricCipherRegistry::class, function (): SymmetricCipherRegistry {
            return new SymmetricCipherRegistry([
                'aes-256-gcm' => new Aes256Gcm,
                'aes-256-cbc' => new Aes256Cbc,
                'aes-128-ctr' => new Aes128Ctr,
                'chacha20-poly1305' => new ChaCha20Poly1305,
                'xsalsa20-poly1305' => new Xsalsa20Poly1305,
            ]);
        });

        $this->app->singleton(AsymmetricCipherRegistry::class, function (): AsymmetricCipherRegistry {
            $aes = new Aes256Gcm;
            $rsa2048 = new RsaOaep(2048);

            return new AsymmetricCipherRegistry([
                'rsa-oaep-2048' => $rsa2048,
                'rsa-oaep-4096' => new RsaOaep(4096),
                'sealed-box' => new SealedBox,
                'hybrid-rsa-aes-256-gcm' => new HybridEncryption($aes, $rsa2048),
            ]);
        });

        $this->app->singleton(SignerRegistry::class, function (): SignerRegistry {
            return new SignerRegistry([
                'rsa-sha256-2048' => new RsaSigner(2048),
                'rsa-sha256-4096' => new RsaSigner(4096),
                'ed25519' => new Ed25519Signer,
                'ecdsa-p256' => new EcdsaSigner('prime256v1', 'ecdsa-p256'),
                'ecdsa-p384' => new EcdsaSigner('secp384r1', 'ecdsa-p384'),
            ]);
        });
    }

    public function boot(): void
    {
        //
    }
}
