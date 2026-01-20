<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceAvis extends Model
{
    use HasFactory;

    protected $table = 'service_avis';

    protected $fillable = [
        'user_id',
        'entreprise_id',
        'type_service_id',
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
     * Relation : Un avis appartient à un type de service
     */
    public function typeService(): BelongsTo
    {
        return $this->belongsTo(TypeService::class);
    }

    /**
     * Relation : Un avis peut être lié à une réservation
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Relation : Un avis peut avoir plusieurs photos (réalisations)
     */
    public function photos(): HasMany
    {
        return $this->hasMany(RealisationPhoto::class, 'service_avis_id');
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
}
