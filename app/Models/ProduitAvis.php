<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProduitAvis extends Model
{
    use HasFactory;

    protected $table = 'produit_avis';

    protected $fillable = [
        'user_id',
        'entreprise_id',
        'produit_id',
        'reservation_id',
        'note',
        'commentaire',
        'est_approuve',
    ];

    protected function casts(): array
    {
        return [
            'note' => 'integer',
            'est_approuve' => 'boolean',
        ];
    }

    /**
     * Relation : Un avis appartient à un utilisateur (client)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : Un avis appartient à une entreprise
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * Relation : Un avis appartient à un produit
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }

    /**
     * Relation : Un avis peut être lié à une réservation
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class)->withDefault();
    }

    /**
     * Relation : Un avis peut avoir plusieurs photos (réalisations)
     */
    public function photos(): HasMany
    {
        return $this->hasMany(RealisationPhoto::class, 'produit_avis_id');
    }

    /**
     * Génère les étoiles pour l'affichage
     */
    public function getEtoilesAttribute(): string
    {
        $etoiles = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $this->note) {
                $etoiles .= '★';
            } else {
                $etoiles .= '☆';
            }
        }
        return $etoiles;
    }

    /**
     * Vérifie si l'avis provient d'un utilisateur ayant payé
     */
    public function aPaiementConfirme(): bool
    {
        if (!$this->reservation_id) {
            return false;
        }

        // Charger la relation si elle n'est pas déjà chargée
        if (!$this->relationLoaded('reservation')) {
            $this->load('reservation');
        }

        $reservation = $this->reservation;
        return $reservation && $reservation->exists && $reservation->est_paye === true;
    }
}
