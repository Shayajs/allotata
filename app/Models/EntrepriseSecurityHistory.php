<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class EntrepriseSecurityHistory extends Model
{
    use HasFactory;

    protected $table = 'entreprise_security_history';

    protected $fillable = [
        'entreprise_id',
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
     * Relation avec l'entreprise
     */
    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * Relation avec l'admin qui a fait le changement
     */
    public function changedByAdmin()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Enregistrer un changement d'email
     */
    public static function recordEmailChange(
        Entreprise $entreprise,
        string $oldEmail,
        string $newEmail,
        ?string $changedBy = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $reason = null
    ): self {
        return self::create([
            'entreprise_id' => $entreprise->id,
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
