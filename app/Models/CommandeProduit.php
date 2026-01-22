<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CommandeProduit extends Model
{
    use HasFactory;

    protected $table = 'commandes_produits';

    protected $fillable = [
        'user_id',
        'entreprise_id',
        'produit_id',
        'membre_id',
        'nom_client',
        'email_client',
        'telephone_client_non_inscrit',
        'telephone_client',
        'telephone_cache',
        'quantite',
        'prix_unitaire',
        'prix_total',
        'notes',
        'mode_livraison',
        'adresse_livraison',
        'code_postal_livraison',
        'ville_livraison',
        'est_paye',
        'date_paiement',
        'statut',
        'date_commande',
        'date_livraison_souhaitee',
        'date_livraison_prevue',
        'date_livraison_reelle',
        'creee_manuellement',
        'hash',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
            'prix_unitaire' => 'decimal:2',
            'prix_total' => 'decimal:2',
            'est_paye' => 'boolean',
            'telephone_cache' => 'boolean',
            'creee_manuellement' => 'boolean',
            'date_commande' => 'datetime',
            'date_paiement' => 'datetime',
            'date_livraison_souhaitee' => 'datetime',
            'date_livraison_prevue' => 'datetime',
            'date_livraison_reelle' => 'datetime',
        ];
    }

    /**
     * Relation : Une commande appartient à un client (User)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : Une commande appartient à une entreprise
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * Relation : Une commande appartient à un produit
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }

    /**
     * Relation : Une commande peut être assignée à un membre
     */
    public function membre(): BelongsTo
    {
        return $this->belongsTo(EntrepriseMembre::class, 'membre_id');
    }

    /**
     * Vérifie si la commande est payée
     */
    public function estPayee(): bool
    {
        return $this->est_paye === true;
    }

    /**
     * Vérifie si la commande est confirmée
     */
    public function estConfirmee(): bool
    {
        return $this->statut === 'confirmee';
    }

    /**
     * Vérifie si la commande est annulée
     */
    public function estAnnulee(): bool
    {
        return $this->statut === 'annulee';
    }

    /**
     * Vérifie si la commande est terminée
     */
    public function estTerminee(): bool
    {
        return $this->statut === 'terminee';
    }

    /**
     * Vérifie si la commande est livrée
     */
    public function estLivree(): bool
    {
        return $this->statut === 'livree';
    }

    /**
     * Formate le prix total avec le symbole €
     */
    public function getPrixTotalFormateAttribute(): string
    {
        return number_format($this->prix_total, 2, ',', ' ') . ' €';
    }

    /**
     * Retourne le nom du client (inscrit ou non inscrit)
     */
    public function getNomClientCompletAttribute(): ?string
    {
        if ($this->attributes['nom_client'] ?? null) {
            return $this->attributes['nom_client'];
        }
        return $this->user?->name;
    }

    /**
     * Retourne l'email du client (inscrit ou non inscrit)
     */
    public function getEmailClientCompletAttribute(): ?string
    {
        if ($this->attributes['email_client'] ?? null) {
            return $this->attributes['email_client'];
        }
        return $this->user?->email;
    }

    /**
     * Génère un hash unique pour la commande
     */
    public function generateHash(): string
    {
        $dateTimestamp = $this->date_commande ? $this->date_commande->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s');
        $dateHash = substr(hash('sha256', $dateTimestamp . config('app.key')), 0, 8);
        
        $membreId = $this->membre_id ?? 0;
        $membreHash = substr(hash('sha256', $membreId . config('app.key')), 0, 8);
        
        $entrepriseHash = substr(hash('sha256', ($this->entreprise_id ?? 0) . config('app.key')), 0, 8);
        
        $uniquePart = substr(hash('sha256', microtime(true) . Str::random(16) . ($this->id ?? Str::random(8))), 0, 8);
        
        return $dateHash . '-' . $membreHash . '-' . $entrepriseHash . '-' . $uniquePart;
    }

    /**
     * Boot method pour générer automatiquement le hash lors de la création
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($commande) {
            if (empty($commande->hash)) {
                do {
                    $commande->hash = $commande->generateHash();
                } while (static::where('hash', $commande->hash)->exists());
            }
        });
    }

    /**
     * Génère un alias court à partir du hash complet
     */
    public function getHashAliasAttribute(): ?string
    {
        if (empty($this->hash)) {
            return null;
        }

        $parts = explode('-', $this->hash);
        if (count($parts) !== 4) {
            return null;
        }

        $alias = '';
        foreach ($parts as $part) {
            $alias .= substr($part, 0, 2);
        }

        return $alias;
    }
}
