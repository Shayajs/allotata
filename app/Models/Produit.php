<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Produit extends Model
{
    use HasFactory;

    protected $fillable = [
        'entreprise_id',
        'nom',
        'description',
        'prix',
        'est_actif',
        'gestion_stock',
        'livraison_disponible',
        'vente_sur_place_disponible',
        'ordre_affichage',
    ];

    protected function casts(): array
    {
        return [
            'prix' => 'decimal:2',
            'est_actif' => 'boolean',
            'livraison_disponible' => 'boolean',
            'vente_sur_place_disponible' => 'boolean',
        ];
    }

    /**
     * Relation : Un produit appartient à une entreprise
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * Relation : Un produit peut avoir un stock
     */
    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class);
    }

    /**
     * Relation : Un produit peut avoir plusieurs images
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProduitImage::class)->orderBy('ordre');
    }

    /**
     * Relation : Récupérer l'image de couverture
     */
    public function imageCouverture(): HasOne
    {
        return $this->hasOne(ProduitImage::class)->where('est_couverture', true);
    }

    /**
     * Relation : Un produit peut avoir plusieurs promotions
     */
    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    /**
     * Récupérer la promotion active actuelle
     */
    public function promotionActive(): HasOne
    {
        $now = now();
        return $this->hasOne(Promotion::class)
            ->where('est_active', true)
            ->where('date_debut', '<=', $now)
            ->where('date_fin', '>=', $now);
    }

    /**
     * Vérifier si le produit a une promotion active
     */
    public function aPromotionActive(): bool
    {
        return $this->promotionActive()->exists();
    }

    /**
     * Obtenir le prix actuel (avec promotion si applicable)
     */
    public function getPrixActuelAttribute(): float
    {
        $promotion = $this->promotionActive()->first();
        return $promotion ? $promotion->prix_promotion : $this->prix;
    }

    /**
     * Vérifier si le produit est disponible
     */
    public function estDisponible(): bool
    {
        // #region agent log
        try {
            $logData = [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'B2',
                'location' => 'Produit.php:' . __LINE__,
                'message' => 'Vérification disponibilité produit',
                'data' => [
                    'produit_id' => $this->id,
                    'est_actif' => $this->est_actif,
                    'gestion_stock' => $this->gestion_stock,
                ],
                'timestamp' => time() * 1000,
            ];
            $logPath = base_path('.cursor/debug.log');
            $logDir = dirname($logPath);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            @file_put_contents($logPath, json_encode($logData) . "\n", FILE_APPEND);
        } catch (\Exception $e) {}
        // #endregion
        
        if (!$this->est_actif) {
            // #region agent log
            $logData = [
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'B2',
                'location' => 'Produit.php:' . __LINE__,
                'message' => 'Produit non actif',
                'data' => ['produit_id' => $this->id],
                'timestamp' => time() * 1000,
            ];
            try {
                $logPath = base_path('.cursor/debug.log');
                $logDir = dirname($logPath);
                if (!is_dir($logDir)) {
                    @mkdir($logDir, 0755, true);
                }
                @file_put_contents($logPath, json_encode($logData) . "\n", FILE_APPEND);
            } catch (\Exception $e) {}
            // #endregion
            return false;
        }

        // Si gestion immédiate, vérifier le stock
        if ($this->gestion_stock === 'disponible_immediatement') {
            $stock = $this->stock;
            $disponible = $stock && $stock->quantite_disponible > 0;
            
            // #region agent log
            try {
                $logData = [
                    'sessionId' => 'debug-session',
                    'runId' => 'run1',
                    'hypothesisId' => 'B2',
                    'location' => 'Produit.php:' . __LINE__,
                    'message' => 'Vérification stock',
                    'data' => [
                        'produit_id' => $this->id,
                        'has_stock' => $stock ? true : false,
                        'quantite_disponible' => $stock ? $stock->quantite_disponible : null,
                        'disponible' => $disponible,
                    ],
                    'timestamp' => time() * 1000,
                ];
                $logPath = base_path('.cursor/debug.log');
            $logDir = dirname($logPath);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            @file_put_contents($logPath, json_encode($logData) . "\n", FILE_APPEND);
            } catch (\Exception $e) {}
            // #endregion
            
            return $disponible;
        }

        // Si en attente de commandes, toujours disponible
        return true;
    }

    /**
     * Relation : Un produit peut avoir plusieurs commandes
     */
    public function commandes(): HasMany
    {
        return $this->hasMany(CommandeProduit::class);
    }

    /**
     * Relation : Un produit peut avoir plusieurs avis
     */
    public function produitAvis(): HasMany
    {
        return $this->hasMany(ProduitAvis::class)->where('est_approuve', true)->orderBy('created_at', 'desc');
    }

    /**
     * Relation : Tous les avis (y compris non approuvés) - pour l'admin
     */
    public function tousProduitAvis(): HasMany
    {
        return $this->hasMany(ProduitAvis::class)->orderBy('created_at', 'desc');
    }

    /**
     * Calcule la note moyenne du produit
     */
    public function getNoteMoyenneAttribute(): float
    {
        $noteMoyenne = $this->produitAvis()->avg('note');
        return $noteMoyenne ? round($noteMoyenne, 1) : 0;
    }

    /**
     * Compte le nombre total d'avis
     */
    public function getNombreAvisAttribute(): int
    {
        return $this->produitAvis()->count();
    }

    /**
     * Récupère les avis triés (payés en haut, autres en bas)
     */
    public function getAvisTriesAttribute()
    {
        $avis = $this->produitAvis()->with(['user:id,name', 'photos', 'reservation'])->get();
        
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

    /**
     * Vérifie si la livraison est disponible pour ce produit
     */
    public function livraisonDisponible(): bool
    {
        // Si défini au niveau produit, utiliser cette valeur
        if ($this->livraison_disponible !== null) {
            return $this->livraison_disponible;
        }
        
        // Sinon, utiliser les paramètres par défaut de l'entreprise
        return $this->entreprise->livraison_disponible_par_defaut ?? true;
    }

    /**
     * Vérifie si la vente sur place est disponible pour ce produit
     */
    public function venteSurPlaceDisponible(): bool
    {
        // Si défini au niveau produit, utiliser cette valeur
        if ($this->vente_sur_place_disponible !== null) {
            return $this->vente_sur_place_disponible;
        }
        
        // Sinon, utiliser les paramètres par défaut de l'entreprise
        return $this->entreprise->vente_sur_place_disponible_par_defaut ?? true;
    }
}
