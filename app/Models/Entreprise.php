<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entreprise extends Model
{
    use HasFactory;

    /**
     * Les attributs que l'utilisateur peut remplir lui-même.
     * Note : 'est_verifiee' est exclu pour éviter toute falsification (Cyber-sécurité).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'nom',
        'slug',
        'type_activite',
        'siren',
        'siren_verifie',
        'status_juridique',
        'email',
        'telephone',
        'description',
        'mots_cles',
        'logo',
        'ville',
        'rayon_deplacement',
        'options_supplementaires', // Pour stocker du JSON (langues, options...)
        'afficher_nom_gerant',
        'nom_valide',
        'nom_refus_raison',
        'siren_valide',
        'siren_refus_raison',
        'raison_refus_globale',
        'image_fond',
        'prix_negociables',
        'rdv_uniquement_messagerie',
        'est_verifiee', // Permet la mise à jour par les contrôleurs admin
    ];

    /**
     * Conversion automatique des types (Casting).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'est_verifiee' => 'boolean', // Transforme le 0/1 de la BDD en true/false PHP
            'siren_verifie' => 'boolean',
            'afficher_nom_gerant' => 'boolean',
            'nom_valide' => 'boolean',
            'siren_valide' => 'boolean',
            'prix_negociables' => 'boolean',
            'rdv_uniquement_messagerie' => 'boolean',
            'rayon_deplacement' => 'integer',
            'options_supplementaires' => 'array', // Transforme le JSON en tableau PHP automatiquement
        ];
    }

    /**
     * Relation : Une entreprise appartient à un utilisateur (le gérant).
     * Concept de clé étrangère : user_id.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : Une entreprise peut avoir plusieurs réservations
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Relation : Une entreprise peut avoir plusieurs horaires d'ouverture
     */
    public function horairesOuverture()
    {
        return $this->hasMany(HorairesOuverture::class);
    }

    /**
     * Relation : Une entreprise peut avoir plusieurs types de services
     */
    public function typesServices()
    {
        return $this->hasMany(TypeService::class);
    }

    /**
     * Relation : Une entreprise peut avoir plusieurs factures
     */
    public function factures()
    {
        return $this->hasMany(Facture::class);
    }

    /**
     * Relation : Une entreprise peut avoir plusieurs avis
     */
    public function avis()
    {
        return $this->hasMany(Avis::class)->where('est_approuve', true)->orderBy('created_at', 'desc');
    }

    /**
     * Relation : Tous les avis (y compris non approuvés) - pour l'admin
     */
    public function tousAvis()
    {
        return $this->hasMany(Avis::class)->orderBy('created_at', 'desc');
    }

    /**
     * Calcule la note moyenne de l'entreprise
     */
    public function getNoteMoyenneAttribute(): float
    {
        $noteMoyenne = $this->avis()->avg('note');
        return $noteMoyenne ? round($noteMoyenne, 1) : 0;
    }

    /**
     * Compte le nombre total d'avis
     */
    public function getNombreAvisAttribute(): int
    {
        return $this->avis()->count();
    }

    /**
     * Exemple de "Helper" : Vérifie si la tata est mobile.
     */
    public function estMobile(): bool
    {
        return $this->rayon_deplacement > 0;
    }

    /**
     * Vérifie si le SIREN est vérifié
     */
    public function sirenEstVerifie(): bool
    {
        return $this->siren_verifie === true && !empty($this->siren);
    }

    /**
     * Relation : Une entreprise peut avoir plusieurs conversations
     */
    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Relation : Une entreprise peut avoir plusieurs photos de réalisations
     */
    public function realisationPhotos()
    {
        return $this->hasMany(RealisationPhoto::class)->orderBy('ordre', 'asc');
    }

    /**
     * Retourne le nom à afficher du gérant
     */
    public function getNomGerantAttribute(): ?string
    {
        if ($this->afficher_nom_gerant && $this->user) {
            return $this->user->name;
        }
        return null;
    }

    /**
     * Vérifie si l'entreprise a un abonnement actif (via son gérant)
     */
    public function aAbonnementActif(): bool
    {
        if ($this->user) {
            return $this->user->aAbonnementActif();
        }

        return false;
    }

    /**
     * Vérifie si tous les éléments sont validés
     */
    public function tousElementsValides(): bool
    {
        // Le nom doit être validé (true)
        if ($this->nom_valide !== true) {
            return false;
        }

        // Si un SIREN est fourni, il doit être explicitement validé (true)
        // Si pas de SIREN, on peut valider l'entreprise sans problème
        if ($this->siren && !empty($this->siren)) {
            // Si un SIREN est fourni, il doit être validé (true)
            if ($this->siren_valide !== true) {
                return false;
            }
        }

        return true;
    }

    /**
     * Vérifie si au moins un élément est refusé
     */
    public function aDesRefus(): bool
    {
        return $this->nom_valide === false || $this->siren_valide === false;
    }
}