<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'login' => 'Inicio de sesión',
            'logout' => 'Cierre de sesión',
            'login_failed' => 'Login fallido',
            'registered' => 'Registro',
            'email_verified' => 'Email verificado',
            'password_reset' => 'Contraseña restablecida',
            'password_updated' => 'Contraseña actualizada',
            'profile_updated' => 'Perfil actualizado',
            'account_deleted' => 'Cuenta eliminada',
            'keypair_created' => 'Llave creada',
            'keypair_deleted' => 'Llave eliminada',
            'demo_entry_created' => 'Entrada demo creada',
            'demo_entry_decrypted' => 'Entrada demo descifrada',
            'demo_entry_deleted' => 'Entrada demo eliminada',
            'demo_entries_exported' => 'Entradas demo exportadas',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }
}
