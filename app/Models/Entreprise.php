<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entreprise extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Obtenir le nom de la clé de route (pour le route model binding)
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

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
        'slug_web',
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
        'contenu_site_web',
        'phrase_accroche',
        'site_web_externe',
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
            'contenu_site_web' => 'array', // Structure JSON pour l'éditeur de site web
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
     * Relation : Une entreprise peut avoir plusieurs abonnements
     */
    public function abonnements()
    {
        return $this->hasMany(EntrepriseSubscription::class);
    }

    /**
     * Relation : Une entreprise peut avoir plusieurs membres
     */
    public function membres()
    {
        return $this->hasMany(EntrepriseMembre::class)->where('est_actif', true);
    }

    /**
     * Relation : Tous les membres (y compris inactifs)
     */
    public function tousMembres()
    {
        return $this->hasMany(EntrepriseMembre::class);
    }

    /**
     * Relation : Les invitations de l'entreprise
     */
    public function invitations()
    {
        return $this->hasMany(EntrepriseInvitation::class);
    }

    /**
     * Relation : Une entreprise peut avoir plusieurs prix personnalisés
     */
    public function customPrices()
    {
        return $this->hasMany(CustomPrice::class);
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
     * Calcule le pourcentage de complétion de l'entreprise (pour les nouvelles entreprises)
     * Retourne un tableau avec les détails de chaque condition
     */
    public function getCompletionStatus(): array
    {
        $conditions = [
            'image' => [
                'label' => 'Ajouter une image d\'entreprise',
                'completed' => !empty($this->logo) || !empty($this->image_fond),
                'route_key' => 'entreprise.dashboard',
                'route_params' => ['slug' => $this->slug, 'tab' => 'parametres'],
            ],
            'agenda' => [
                'label' => 'Gérer l\'agenda',
                'completed' => $this->aAgendaConfigure(),
                'route_key' => 'entreprise.dashboard',
                'route_params' => ['slug' => $this->slug, 'tab' => 'agenda'],
            ],
            'description' => [
                'label' => 'Mettre une description',
                'completed' => !empty($this->description) && strlen(trim($this->description)) > 0,
                'route_key' => 'entreprise.dashboard',
                'route_params' => ['slug' => $this->slug, 'tab' => 'parametres'],
            ],
            'service' => [
                'label' => 'Ajouter un premier service',
                'completed' => $this->typesServices()->where('est_actif', true)->count() > 0,
                'route_key' => 'entreprise.dashboard',
                'route_params' => ['slug' => $this->slug, 'tab' => 'services'],
            ],
        ];

        // Générer les routes
        foreach ($conditions as $key => &$condition) {
            try {
                $condition['route'] = route($condition['route_key'], $condition['route_params']);
            } catch (\Exception $e) {
                $condition['route'] = '#';
            }
            unset($condition['route_key'], $condition['route_params']);
        }

        $completed = collect($conditions)->where('completed', true)->count();
        $total = count($conditions);
        $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;

        return [
            'conditions' => $conditions,
            'completed' => $completed,
            'total' => $total,
            'percentage' => $percentage,
            'isComplete' => $completed === $total,
        ];
    }

    /**
     * Vérifie si l'agenda est configuré (au moins un jour n'est pas fermé)
     */
    public function aAgendaConfigure(): bool
    {
        $horaires = $this->horairesOuverture()
            ->where('est_exceptionnel', false)
            ->get();
        
        // Vérifier qu'au moins un jour a des horaires (n'est pas fermé)
        return $horaires->contains(function($horaire) {
            return !empty($horaire->heure_ouverture) && !empty($horaire->heure_fermeture);
        });
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

    /**
     * Vérifie si l'entreprise a un abonnement site web actif
     */
    public function aSiteWebActif(): bool
    {
        $subscription = $this->abonnements()
            ->where('type', 'site_web')
            ->first();

        return $subscription && $subscription->estActif();
    }

    /**
     * Vérifie si l'entreprise a un abonnement multi-personnes actif
     */
    public function aGestionMultiPersonnes(): bool
    {
        $subscription = $this->abonnements()
            ->where('type', 'multi_personnes')
            ->first();

        return $subscription && $subscription->estActif();
    }

    /**
     * Récupère l'abonnement site web
     */
    public function abonnementSiteWeb()
    {
        return $this->abonnements()->where('type', 'site_web')->first();
    }

    /**
     * Récupère l'abonnement multi-personnes
     */
    public function abonnementMultiPersonnes()
    {
        return $this->abonnements()->where('type', 'multi_personnes')->first();
    }

    /**
     * Vérifie si un utilisateur est membre de l'entreprise (actif ou inactif)
     */
    public function aMembre(User $user): bool
    {
        return $this->tousMembres()->where('user_id', $user->id)->exists();
    }

    /**
     * Vérifie si un utilisateur est administrateur de l'entreprise
     */
    public function aAdministrateur(User $user): bool
    {
        // Le propriétaire (user_id) est toujours administrateur
        if ((int)$this->user_id === (int)$user->id) {
            return true;
        }

        // Vérifier si l'utilisateur est membre avec le rôle administrateur
        $membre = $this->membres()->where('user_id', $user->id)->first();
        return $membre && $membre->estAdministrateur();
    }

    /**
     * Vérifie si un utilisateur peut gérer l'entreprise (propriétaire ou administrateur)
     */
    public function peutEtreGereePar(User $user): bool
    {
        // Comparaison stricte avec conversion de type pour éviter les problèmes de type
        $estProprietaire = (int)$this->user_id === (int)$user->id;
        
        // Si c'est le propriétaire, retourner true directement
        if ($estProprietaire) {
            return true;
        }
        
        // Sinon, vérifier si l'utilisateur est administrateur membre
        return $this->aAdministrateur($user);
    }

    /**
     * Retourne la structure par défaut du site web vitrine
     */
    public static function getDefaultSiteWebContent(): array
    {
        return [
            'theme' => [
                'colors' => [
                    'primary' => '#22c55e',
                    'secondary' => '#f97316',
                    'accent' => '#3b82f6',
                    'background' => '#ffffff',
                    'text' => '#1e293b',
                ],
                'fonts' => [
                    'heading' => 'Poppins',
                    'body' => 'Inter',
                ],
                'buttons' => [
                    'style' => 'rounded', // rounded, square, pill
                    'shadow' => true,
                ],
            ],
            'blocks' => [],
            'version' => 1,
            'lastSaved' => null,
        ];
    }

    /**
     * Récupère le contenu du site web avec les valeurs par défaut
     */
    public function getSiteWebContentAttribute(): array
    {
        $content = $this->contenu_site_web;
        
        if (empty($content)) {
            return self::getDefaultSiteWebContent();
        }
        
        // Fusionner avec les valeurs par défaut pour s'assurer que toutes les clés existent
        $default = self::getDefaultSiteWebContent();
        
        return array_replace_recursive($default, $content);
    }

    /**
     * Récupère les blocs du site web
     */
    public function getSiteWebBlocks(): array
    {
        $content = $this->site_web_content;
        return $content['blocks'] ?? [];
    }

    /**
     * Récupère le thème du site web
     */
    public function getSiteWebTheme(): array
    {
        $content = $this->site_web_content;
        return $content['theme'] ?? self::getDefaultSiteWebContent()['theme'];
    }

    /**
     * Vérifie si l'entreprise peut être archivée (supprimée par l'utilisateur)
     * Possible uniquement si aucun abonnement actif lié à l'entreprise.
     * Note: On ne vérifie PAS l'abonnement utilisateur, seulement les abonnements entreprise.
     */
    public function canBeArchived(): bool
    {
        // Vérifier uniquement les abonnements liés à l'entreprise (site_web, multi_personnes)
        return !$this->aSiteWebActif() && !$this->aGestionMultiPersonnes();
    }

    /**
     * Vérifie si l'entreprise est archivée (soft deleted)
     */
    public function isArchived(): bool
    {
        return $this->trashed();
    }

    /**
     * Vérifie si l'entreprise peut être restaurée par l'utilisateur
     * Possible pendant 30 jours après l'archivage.
     */
    public function canBeRestoredByUser(): bool
    {
        if (!$this->isArchived()) {
            return false;
        }

        // Si supprimé il y a moins de 30 jours
        return $this->deleted_at->addDays(30)->isFuture();
    }

    /**
     * Retourne le nombre de jours restants avant suppression définitive (vue utilisateur)
     */
    public function daysUntilPermanentDeletion(): int
    {
        if (!$this->isArchived()) {
            return 30;
        }

        $remaining = now()->diffInDays($this->deleted_at->addDays(30), false);
        return max(0, (int)$remaining);
    }
}