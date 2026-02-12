<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class UserSecurityHistory extends Model
{
    use HasFactory;

    protected $table = 'user_security_history';

    protected $fillable = [
        'user_id',
        'type',
        'old_value_hash',
        'new_value_hash',
        'changed_by',
        'ip_address',
        'user_agent',
        'reason',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec l'admin qui a fait le changement
     */
    public function changedByAdmin()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Vérifier si un mot de passe correspond à un ancien hash
     */
    public static function checkOldPassword(User $user, string $password): bool
    {
        $history = self::where('user_id', $user->id)
            ->where('type', 'password')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($history as $record) {
            if ($record->old_value_hash && \Hash::check($password, $record->old_value_hash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enregistrer un changement de mot de passe
     */
    public static function recordPasswordChange(
        User $user,
        string $oldPasswordHash,
        ?string $changedBy = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $reason = null
    ): self {
        return self::create([
            'user_id' => $user->id,
            'type' => 'password',
            'old_value_hash' => $oldPasswordHash,
            'new_value_hash' => $user->password, // Le nouveau hash est déjà dans $user->password
            'changed_by' => $changedBy,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'reason' => $reason,
        ]);
    }

    /**
     * Enregistrer un changement d'email
     */
    public static function recordEmailChange(
        User $user,
        string $oldEmail,
        string $newEmail,
        ?string $changedBy = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $reason = null
    ): self {
        return self::create([
            'user_id' => $user->id,
            'type' => 'email',
            'old_value_hash' => Crypt::encryptString($oldEmail),
            'new_value_hash' => Crypt::encryptString($newEmail),
            'changed_by' => $changedBy,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'reason' => $reason,
        ]);
    }

    /**
     * Récupérer l'ancien email déchiffré
     */
    public function getOldEmailAttribute(): ?string
    {
        if ($this->type === 'email' && $this->old_value_hash) {
            try {
                return Crypt::decryptString($this->old_value_hash);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Récupérer le nouveau email déchiffré
     */
    public function getNewEmailAttribute(): ?string
    {
        if ($this->type === 'email' && $this->new_value_hash) {
            try {
                return Crypt::decryptString($this->new_value_hash);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }
}
