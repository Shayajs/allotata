<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntrepriseModification extends Model
{
    public const STATUT_PENDING = 'pending';

    public const STATUT_APPROVED = 'approved';

    public const STATUT_REJECTED = 'rejected';

    public const FIELD_LABELS = [
        'nom' => 'Nom',
        'slug' => 'Slug',
        'type_activite' => 'Type d\'activité',
        'description' => 'Description',
        'mots_cles' => 'Mots-clés',
        'ville' => 'Ville',
        'adresse_rue' => 'Adresse',
        'code_postal' => 'Code postal',
        'latitude' => 'Latitude',
        'longitude' => 'Longitude',
        'afficher_adresse_complete' => 'Afficher l\'adresse complète',
        'type_localisation' => 'Type de localisation',
        'rayon_deplacement' => 'Rayon de déplacement',
        'siren' => 'SIREN',
        'siret' => 'SIRET',
        'video_url' => 'Vidéo',
        'afficher_video' => 'Afficher la vidéo',
        'site_web_externe' => 'Site web externe',
        'phrase_accroche' => 'Phrase d\'accroche',
        'logo' => 'Logo',
        'image_fond' => 'Image de fond',
    ];

    protected $fillable = [
        'entreprise_id',
        'user_id',
        'payload',
        'statut',
        'reviewed_by',
        'reviewed_at',
        'motif_refus',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('statut', self::STATUT_PENDING);
    }

    public function estEnAttente(): bool
    {
        return $this->statut === self::STATUT_PENDING;
    }

    /**
     * @return array<string, mixed>
     */
    public function fields(): array
    {
        return $this->payload['fields'] ?? [];
    }

    public function hasMediaChanges(): bool
    {
        $payload = $this->payload ?? [];

        return array_key_exists('logo', $payload)
            || array_key_exists('image_fond', $payload)
            || ! empty($payload['photos_add'])
            || ! empty($payload['photos_delete']);
    }
}
