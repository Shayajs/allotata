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
}
