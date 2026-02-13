<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enfant extends Model
{
    protected $fillable = [
        'user_id',
        'prenom',
        'date_naissance',
        'notes',
    ];

    protected $casts = [
        'date_naissance' => 'date',
    ];

    /**
     * Relation avec le parent (utilisateur)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculer l'âge dynamiquement
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->date_naissance) {
            return null;
        }

        return $this->date_naissance->age;
    }

    /**
     * Obtenir l'âge formaté (ex: "3 ans", "8 mois")
     */
    public function getAgeFormateAttribute(): ?string
    {
        if (!$this->date_naissance) {
            return null;
        }

        $age = $this->date_naissance->age;
        if ($age >= 1) {
            return $age . ' an' . ($age > 1 ? 's' : '');
        }

        $mois = $this->date_naissance->diffInMonths(now());
        return $mois . ' mois';
    }
}
