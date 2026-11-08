<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crypto_demo_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('username_plain');
            $table->longText('username_encrypted');
            $table->text('password_plain');
            $table->longText('password_encrypted');
            $table->string('sym_algorithm', 60);
            $table->string('asym_algorithm', 60);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crypto_demo_entries');
    }
};
