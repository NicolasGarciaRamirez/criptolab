<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Crypto\BlindIndexService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RehashEmailsCommand extends Command
{
    protected $signature = 'crypto:rehash-emails';

    protected $description = 'Recalcula email_hash con la CRYPTO_BLIND_INDEX_KEY actual e invalida tokens de recuperación';

    public function handle(BlindIndexService $blindIndex): int
    {
        $updated = 0;

        User::query()->orderBy('id')->chunkById(100, function ($users) use ($blindIndex, &$updated): void {
            foreach ($users as $user) {
                $user->email_hash = $blindIndex->hash((string) $user->email);
                $user->saveQuietly();
                $updated++;
            }
        });

        DB::table('password_reset_tokens')->delete();

        $this->info("Se recalcularon {$updated} índices de email y se invalidaron los tokens de recuperación.");

        return self::SUCCESS;
    }
}
