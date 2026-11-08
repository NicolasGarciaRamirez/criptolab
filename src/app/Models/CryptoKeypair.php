<?php

namespace App\Models;

use App\Casts\EncryptedWithMasterKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoKeypair extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'algorithm',
        'public_key',
        'private_key_encrypted',
        'fingerprint',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'private_key_encrypted',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'private_key_encrypted' => EncryptedWithMasterKey::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
