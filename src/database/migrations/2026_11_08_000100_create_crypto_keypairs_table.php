<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crypto_keypairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('algorithm', 60);
            $table->longText('public_key');
            $table->longText('private_key_encrypted');
            $table->string('fingerprint', 64);
            $table->timestamps();

            $table->index(['user_id', 'algorithm']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crypto_keypairs');
    }
};
