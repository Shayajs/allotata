<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EssaiGratuit extends Model
{
    use HasFactory;

    protected $table = 'essais_gratuits';

    protected $fillable = [
        'essayable_type',
        'essayable_id',
        'type_abonnement',
        'date_debut',
        'date_fin',
        'duree_jours',
        'statut',
        'date_conversion',
        'date_annulation',
        'raison_annulation',
        'notification_rappel_envoye_le',
        'notification_expiration_envoye_le',
        'notification_relance_envoye_le',
        'nb_connexions',
        'derniere_connexion',
        'nb_actions',
        'metriques',
        'source',
        'code_promo_utilise',
        'parrain_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'ip_activation',
        'user_agent',
        'accorde_par_admin_id',
        'notes_admin',
        'valeur_essai',
        'abonnement_converti_id',
        'abonnement_converti_type',
        'note_satisfaction',
        'feedback',
        'raison_non_conversion',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'datetime',
            'date_fin' => 'datetime',
            'date_conversion' => 'datetime',
            'date_annulation' => 'datetime',
            'notification_rappel_envoye_le' => 'datetime',
            'notification_expiration_envoye_le' => 'datetime',
            'notification_relance_envoye_le' => 'datetime',
            'derniere_connexion' => 'datetime',
            'metriques' => 'array',
            'valeur_essai' => 'decimal:2',
            'duree_jours' => 'integer',
            'nb_connexions' => 'integer',
            'nb_actions' => 'integer',
            'note_satisfaction' => 'integer',
        ];
    }

    // ═══════════════════════════════════════════
    // RELATIONS
    // ═══════════════════════════════════════════

    /**
     * Relation polymorphique : User ou Entreprise
     */
    public function essayable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * L'admin qui a accordé l'essai (si manuel)
     */
    public function accordeParAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accorde_par_admin_id');
    }

    /**
     * Le parrain (si parrainage)
     */
    public function parrain(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parrain_id');
    }

    /**
     * L'abonnement converti (polymorphique)
     */
    public function abonnementConverti(): MorphTo
    {
        return $this->morphTo('abonnement_converti');
    }

    // ═══════════════════════════════════════════
    // HELPERS - STATUT
    // ═══════════════════════════════════════════

    /**
     * Vérifie si l'essai est actuellement actif
     */
    public function estEnCours(): bool
    {
        return $this->statut === 'actif' && $this->date_fin->isFuture();
    }

    /**
     * Vérifie si l'essai est expiré
     */
    public function estExpire(): bool
    {
        return $this->statut === 'expire' || 
               ($this->statut === 'actif' && $this->date_fin->isPast());
    }

    /**
     * Vérifie si l'essai a été converti
     */
    public function estConverti(): bool
    {
        return $this->statut === 'converti';
    }

    /**
     * Nombre de jours restants
     */
    public function joursRestants(): int
    {
        if ($this->date_fin->isPast()) {
            return 0;
        }
        return (int) now()->diffInDays($this->date_fin, false);
    }

    /**
     * Nombre d'heures restantes
     */
    public function heuresRestantes(): int
    {
        if ($this->date_fin->isPast()) {
            return 0;
        }
        return (int) now()->diffInHours($this->date_fin, false);
    }

    // ═══════════════════════════════════════════
    // ACTIONS
    // ═══════════════════════════════════════════

    /**
     * Marque l'essai comme expiré
     */
    public function marquerExpire(): void
    {
        $this->update(['statut' => 'expire']);
    }

    /**
     * Marque l'essai comme converti
     */
    public function marquerConverti($abonnement = null): void
    {
        $data = [
            'statut' => 'converti',
            'date_conversion' => now(),
        ];

        if ($abonnement) {
            $data['abonnement_converti_id'] = $abonnement->id;
            $data['abonnement_converti_type'] = get_class($abonnement);
        }

        $this->update($data);
    }

    /**
     * Annule l'essai
     */
    public function annuler(string $raison = null): void
    {
        $this->update([
            'statut' => 'annule',
            'date_annulation' => now(),
            'raison_annulation' => $raison,
        ]);
    }

    /**
     * Révoque l'essai (par admin)
     */
    public function revoquer(string $raison = null): void
    {
        $this->update([
            'statut' => 'revoque',
            'date_annulation' => now(),
            'raison_annulation' => $raison,
        ]);
    }

    /**
     * Incrémente le compteur de connexions
     */
    public function enregistrerConnexion(): void
    {
        $this->increment('nb_connexions');
        $this->update(['derniere_connexion' => now()]);
    }

    /**
     * Incrémente le compteur d'actions
     */
    public function enregistrerAction(): void
    {
        $this->increment('nb_actions');
    }

    /**
     * Met à jour les métriques spécifiques
     */
    public function mettreAJourMetrique(string $cle, $valeur): void
    {
        $metriques = $this->metriques ?? [];
        $metriques[$cle] = $valeur;
        $this->update(['metriques' => $metriques]);
    }

    /**
     * Incrémente une métrique spécifique
     */
    public function incrementerMetrique(string $cle, int $increment = 1): void
    {
        $metriques = $this->metriques ?? [];
        $metriques[$cle] = ($metriques[$cle] ?? 0) + $increment;
        $this->update(['metriques' => $metriques]);
    }

    // ═══════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════

    /**
     * Essais actifs
     */
    public function scopeActifs($query)
    {
        return $query->where('statut', 'actif')
                     ->where('date_fin', '>', now());
    }

    /**
     * Essais expirés (à traiter)
     */
    public function scopeAExpirer($query)
    {
        return $query->where('statut', 'actif')
                     ->where('date_fin', '<=', now());
    }

    /**
     * Essais expirant bientôt (pour notifications)
     */
    public function scopeExpirantDans($query, int $jours)
    {
        return $query->where('statut', 'actif')
                     ->whereBetween('date_fin', [now(), now()->addDays($jours)]);
    }

    /**
     * Essais convertis
     */
    public function scopeConvertis($query)
    {
        return $query->where('statut', 'converti');
    }

    /**
     * Par type d'abonnement
     */
    public function scopeParType($query, string $type)
    {
        return $query->where('type_abonnement', $type);
    }

    /**
     * Par source
     */
    public function scopeParSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Dans l'année
     */
    public function scopeDansLAnnee($query)
    {
        return $query->where('date_debut', '>=', now()->subYear());
    }

    // ═══════════════════════════════════════════
    // CONSTANTES & TYPES
    // ═══════════════════════════════════════════

    /**
     * Types d'abonnements disponibles pour essai
     */
    public static function getTypesAbonnement(): array
    {
        return [
            'premium' => [
                'label' => 'Allo Tata Premium',
                'description' => 'Accès complet à toutes les fonctionnalités premium',
                'cible' => 'user',
                'valeur_mensuelle' => 15.00,
            ],
            'site_web' => [
                'label' => 'Site Web Vitrine',
                'description' => 'Page vitrine personnalisée pour votre entreprise',
                'cible' => 'entreprise',
                'valeur_mensuelle' => 2.00,
            ],
            'multi_personnes' => [
                'label' => 'Gestion Multi-Personnes',
                'description' => 'Gérez plusieurs membres dans votre entreprise',
                'cible' => 'entreprise',
                'valeur_mensuelle' => 20.00,
            ],
        ];
    }

    /**
     * Sources possibles
     */
    public static function getSources(): array
    {
        return [
            'inscription' => 'Essai automatique à l\'inscription',
            'bouton_cta' => 'Clic sur "Essayer gratuitement"',
            'admin_manuel' => 'Accordé manuellement par admin',
            'code_promo' => 'Via un code promotionnel',
            'parrainage' => 'Via un parrain',
            'campagne_email' => 'Lien d\'une campagne email',
            'support' => 'Suite à un ticket support',
        ];
    }

    /**
     * Raisons de non-conversion
     */
    public static function getRaisonsNonConversion(): array
    {
        return [
            'trop_cher' => 'Trop cher',
            'pas_besoin' => 'Je n\'en ai pas besoin',
            'pas_eu_le_temps' => 'Je n\'ai pas eu le temps de tester',
            'concurrent' => 'J\'utilise un concurrent',
            'fonctionnalites_manquantes' => 'Fonctionnalités manquantes',
            'autre' => 'Autre raison',
        ];
    }

    /**
     * Statuts possibles
     */
    public static function getStatuts(): array
    {
        return [
            'actif' => ['label' => 'Actif', 'color' => 'green'],
            'expire' => ['label' => 'Expiré', 'color' => 'gray'],
            'converti' => ['label' => 'Converti', 'color' => 'blue'],
            'annule' => ['label' => 'Annulé', 'color' => 'yellow'],
            'revoque' => ['label' => 'Révoqué', 'color' => 'red'],
        ];
    }
}
