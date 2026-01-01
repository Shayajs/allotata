<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntrepriseMembre extends Model
{
    use HasFactory;

    protected $fillable = [
        'entreprise_id',
        'user_id',
        'role',
        'est_actif',
        'invite_at',
        'accepte_at',
    ];

    protected function casts(): array
    {
        return [
            'est_actif' => 'boolean',
            'invite_at' => 'datetime',
            'accepte_at' => 'datetime',
        ];
    }

    /**
     * Relation : Un membre appartient à une entreprise
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * Relation : Un membre est un utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifie si le membre est administrateur
     */
    public function estAdministrateur(): bool
    {
        return $this->role === 'administrateur';
    }

    /**
     * Vérifie si l'invitation a été acceptée
     */
    public function estAccepte(): bool
    {
        return $this->accepte_at !== null;
    }

    /**
     * Rôles disponibles
     */
    public static function getRoles(): array
    {
        return [
            'administrateur' => 'Administrateur',
            'membre' => 'Membre',
        ];
    }
}
