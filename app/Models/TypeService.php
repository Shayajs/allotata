<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TypeService extends Model
{
    use HasFactory;

    protected $table = 'types_services';

    protected $fillable = [
        'entreprise_id',
        'nom',
        'description',
        'duree_minutes',
        'prix',
        'est_actif',
    ];

    protected function casts(): array
    {
        return [
            'duree_minutes' => 'integer',
            'prix' => 'decimal:2',
            'est_actif' => 'boolean',
        ];
    }

    /**
     * Relation : Un type de service appartient à une entreprise
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * Relation : Un type de service peut avoir plusieurs réservations
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Relation : Un type de service peut avoir plusieurs images
     */
    public function images(): HasMany
    {
        return $this->hasMany(ServiceImage::class)->orderBy('ordre');
    }

    /**
     * Relation : Récupérer l'image de couverture
     */
    public function imageCouverture(): HasOne
    {
        return $this->hasOne(ServiceImage::class)->where('est_couverture', true);
    }

    /**
     * Relation : Un type de service peut avoir plusieurs avis
     */
    public function serviceAvis(): HasMany
    {
        return $this->hasMany(ServiceAvis::class)->where('est_approuve', true)->orderBy('created_at', 'desc');
    }

    /**
     * Relation : Tous les avis (y compris non approuvés) - pour l'admin
     */
    public function tousServiceAvis(): HasMany
    {
        return $this->hasMany(ServiceAvis::class)->orderBy('created_at', 'desc');
    }

    /**
     * Calcule la note moyenne du service
     */
    public function getNoteMoyenneAttribute(): float
    {
        $noteMoyenne = $this->serviceAvis()->avg('note');
        return $noteMoyenne ? round($noteMoyenne, 1) : 0;
    }

    /**
     * Compte le nombre total d'avis
     */
    public function getNombreAvisAttribute(): int
    {
        return $this->serviceAvis()->count();
    }

    /**
     * Récupère les avis triés (payés en haut, autres en bas)
     */
    public function getAvisTriesAttribute()
    {
        $avis = $this->serviceAvis()->with(['user:id,name', 'photos', 'reservation'])->get();
        
        // Séparer les avis avec paiement confirmé et les autres
        $avisPayes = $avis->filter(function($avis) {
            return $avis->aPaiementConfirme();
        })->sortByDesc('created_at');
        
        $avisAutres = $avis->filter(function($avis) {
            return !$avis->aPaiementConfirme();
        })->sortByDesc('created_at');
        
        // Retourner les payés en premier, puis les autres
        return $avisPayes->merge($avisAutres);
    }
}
