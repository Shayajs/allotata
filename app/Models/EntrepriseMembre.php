<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * Relation : Un membre a plusieurs disponibilités (horaires réguliers)
     */
    public function disponibilites(): HasMany
    {
        return $this->hasMany(MembreDisponibilite::class, 'membre_id');
    }

    /**
     * Relation : Un membre a plusieurs indisponibilités ponctuelles
     */
    public function indisponibilites(): HasMany
    {
        return $this->hasMany(MembreIndisponibilite::class, 'membre_id');
    }

    /**
     * Relation : Un membre a plusieurs réservations
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'membre_id');
    }

    /**
     * Relation : Un membre a plusieurs statistiques
     */
    public function statistiques(): HasMany
    {
        return $this->hasMany(MembreStatistique::class, 'membre_id');
    }

    /**
     * Vérifie si le membre est disponible à un créneau donné
     */
    public function estDisponible(\Carbon\Carbon $date, ?\Carbon\Carbon $heureDebut = null, ?\Carbon\Carbon $heureFin = null): bool
    {
        // Vérifier les indisponibilités ponctuelles
        foreach ($this->indisponibilites as $indispo) {
            if ($indispo->chevaucheAvec($date, $heureDebut, $heureFin)) {
                return false;
            }
        }

        // Vérifier les horaires réguliers
        $jourSemaine = $date->dayOfWeek;
        $disponibilite = $this->disponibilites()
            ->where('jour_semaine', $jourSemaine)
            ->where('est_exceptionnel', false)
            ->first();

        if (!$disponibilite || !$disponibilite->estDisponible()) {
            return false;
        }

        // Si des heures sont spécifiées, vérifier qu'elles sont dans la plage
        if ($heureDebut && $heureFin && $disponibilite->heure_debut && $disponibilite->heure_fin) {
            $heureDebutStr = $heureDebut->format('H:i:s');
            $heureFinStr = $heureFin->format('H:i:s');
            return $heureDebutStr >= $disponibilite->heure_debut && $heureFinStr <= $disponibilite->heure_fin;
        }

        return true;
    }

    /**
     * Calcule la charge de travail sur une période
     */
    public function getChargeTravail(\Carbon\Carbon $dateDebut, \Carbon\Carbon $dateFin): array
    {
        return MembreStatistique::calculerChargeTravail($this, $dateDebut, $dateFin);
    }

    /**
     * Récupère le revenu mensuel
     */
    public function getRevenuMensuel(\Carbon\Carbon $mois = null): float
    {
        if (!$mois) {
            $mois = now();
        }

        $dateDebut = $mois->copy()->startOfMonth();
        $dateFin = $mois->copy()->endOfMonth();

        $stats = $this->getChargeTravail($dateDebut, $dateFin);
        return (float) $stats['revenu_total'];
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
