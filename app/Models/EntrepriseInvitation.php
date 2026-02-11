<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EntrepriseInvitation extends Model
{
    protected $fillable = [
        'entreprise_id',
        'email',
        'role',
        'statut',
        'token',
        'invite_par_user_id',
        'user_id',
        'accepte_at',
        'refuse_at',
        'expire_at',
    ];

    protected $casts = [
        'accepte_at' => 'datetime',
        'refuse_at' => 'datetime',
        'expire_at' => 'datetime',
    ];

    /**
     * Génère un token unique pour l'invitation
     */
    public static function genererToken(): string
    {
        do {
            $token = Str::random(64);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    /**
     * Relation : Une invitation appartient à une entreprise
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * Relation : Une invitation appartient à un utilisateur (celui qui invite)
     */
    public function invitePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invite_par_user_id');
    }

    /**
     * Relation : Une invitation peut être associée à un utilisateur (celui qui est invité)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifie si l'invitation est en attente de création de compte
     */
    public function estEnAttenteCompte(): bool
    {
        return $this->statut === 'en_attente_compte';
    }

    /**
     * Vérifie si l'invitation est en attente d'acceptation
     */
    public function estEnAttenteAcceptation(): bool
    {
        return $this->statut === 'en_attente_acceptation';
    }

    /**
     * Vérifie si l'invitation est acceptée
     */
    public function estAcceptee(): bool
    {
        return $this->statut === 'acceptee';
    }

    /**
     * Vérifie si l'invitation est refusée
     */
    public function estRefusee(): bool
    {
        return $this->statut === 'refusee';
    }

    /**
     * Vérifie si l'invitation est expirée
     */
    public function estExpiree(): bool
    {
        return $this->expire_at && $this->expire_at->isPast();
    }

    /**
     * Convertit une invitation en attente de compte en invitation de membre
     */
    public function convertirEnInvitationMembre(User $user): void
    {
        $this->update([
            'statut' => 'en_attente_acceptation',
            'user_id' => $user->id,
        ]);
    }

    /**
     * Marque l'invitation comme acceptée
     */
    public function marquerAcceptee(): void
    {
        $this->update([
            'statut' => 'acceptee',
            'accepte_at' => now(),
        ]);
    }

    /**
     * Marque l'invitation comme refusée
     */
    public function marquerRefusee(): void
    {
        $this->update([
            'statut' => 'refusee',
            'refuse_at' => now(),
        ]);
    }
}
