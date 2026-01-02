<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntrepriseSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'entreprise_id',
        'type',
        'name',
        'stripe_id',
        'stripe_status',
        'stripe_price',
        'est_manuel',
        'actif_jusqu',
        'notes_manuel',
        'type_renouvellement',
        'jour_renouvellement',
        'date_debut',
        'montant',
        'trial_ends_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'est_manuel' => 'boolean',
            'actif_jusqu' => 'date',
            'date_debut' => 'date',
            'montant' => 'decimal:2',
            'trial_ends_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * Relation : Un abonnement appartient à une entreprise
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * Vérifie si l'abonnement est actif
     */
    public function estActif(): bool
    {
        // Si c'est un abonnement manuel
        if ($this->est_manuel) {
            if ($this->actif_jusqu) {
                return $this->actif_jusqu->isFuture() || $this->actif_jusqu->isToday();
            }
            return false;
        }

        // Si c'est un abonnement Stripe
        if ($this->stripe_id && $this->stripe_status) {
            // Si l'abonnement est annulé mais en période de grâce, il est encore actif
            if ($this->stripe_status === 'active' && $this->ends_at && $this->ends_at->isFuture()) {
                return true; // En période de grâce, toujours actif
            }
            
            // Vérifier le statut
            if ($this->stripe_status === 'active' || $this->stripe_status === 'trialing') {
                // Si ends_at est défini et dans le passé, l'abonnement n'est plus actif
                if ($this->ends_at && $this->ends_at->isPast()) {
                    return false;
                }
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si l'abonnement est en période d'essai
     */
    public function estEnEssai(): bool
    {
        if ($this->trial_ends_at) {
            return $this->trial_ends_at->isFuture();
        }
        return false;
    }

    /**
     * Types d'abonnements disponibles
     */
    public static function getTypes(): array
    {
        return [
            'site_web' => 'Site Web Vitrine (2€/mois)',
            'multi_personnes' => 'Gestion Multi-Personnes (20€/mois)',
        ];
    }
}
