<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Services\Crypto\BlindIndexService;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'role',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'user',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_hash',
        'phone',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'email' => 'encrypted',
            'phone' => 'encrypted',
            'role' => UserRole::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            if ($user->isDirty('email') && filled($user->email)) {
                $user->email_hash = app(BlindIndexService::class)->hash((string) $user->email);
            }
        });
    }

    public function setUsernameAttribute(string $value): void
    {
        $this->attributes['username'] = Str::lower(trim($value));
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * @return HasMany<CryptoDemoEntry, $this>
     */
    public function cryptoDemoEntries(): HasMany
    {
        return $this->hasMany(CryptoDemoEntry::class);
    }

    /**
     * @return HasMany<CryptoKeypair, $this>
     */
    public function cryptoKeypairs(): HasMany
    {
        return $this->hasMany(CryptoKeypair::class);
    }

    /**
     * @return HasMany<CryptoOperation, $this>
     */
    public function cryptoOperations(): HasMany
    {
        return $this->hasMany(CryptoOperation::class);
    }

    /**
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
