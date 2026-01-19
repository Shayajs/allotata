<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'entreprise_id',
        'membre_id',
        'date_reservation',
        'lieu',
        'telephone_client',
        'telephone_cache',
        'notes',
        'prix',
        'est_paye',
        'date_paiement',
        'statut',
        'type_service',
        'type_service_id',
        'duree_minutes',
        'nom_client',
        'email_client',
        'telephone_client_non_inscrit',
        'creee_manuellement',
        'hash',
    ];

    protected function casts(): array
    {
        return [
            'date_reservation' => 'datetime',
            'date_paiement' => 'datetime',
            'prix' => 'decimal:2',
            'est_paye' => 'boolean',
            'telephone_cache' => 'boolean',
            'duree_minutes' => 'integer',
            'creee_manuellement' => 'boolean',
        ];
    }

    /**
     * Relation : Une réservation appartient à un client (User)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : Une réservation appartient à une entreprise
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * Relation : Une réservation peut avoir un type de service
     */
    public function typeService(): BelongsTo
    {
        return $this->belongsTo(TypeService::class);
    }

    /**
     * Relation : Une réservation peut être assignée à un membre
     */
    public function membre(): BelongsTo
    {
        return $this->belongsTo(EntrepriseMembre::class, 'membre_id');
    }

    /**
     * Relation : Une réservation peut avoir une facture (facture simple)
     */
    public function facture()
    {
        return $this->hasOne(Facture::class);
    }

    /**
     * Relation : Une réservation peut appartenir à plusieurs factures groupées
     */
    public function facturesGroupes(): BelongsToMany
    {
        return $this->belongsToMany(Facture::class, 'facture_reservation')
            ->withTimestamps();
    }

    /**
     * Vérifie si la réservation a déjà une facture (simple ou groupée)
     */
    public function aDejaFacture(): bool
    {
        return $this->facture !== null || $this->facturesGroupes()->exists();
    }

    /**
     * Relation : Une réservation peut avoir une conversation
     */
    public function conversation()
    {
        return $this->hasOne(Conversation::class);
    }

    /**
     * Vérifie si la réservation est payée
     */
    public function estPayee(): bool
    {
        return $this->est_paye === true;
    }

    /**
     * Vérifie si la réservation est confirmée
     */
    public function estConfirmee(): bool
    {
        return $this->statut === 'confirmee';
    }

    /**
     * Vérifie si la réservation est annulée
     */
    public function estAnnulee(): bool
    {
        return $this->statut === 'annulee';
    }

    /**
     * Formate le prix avec le symbole €
     */
    public function getPrixFormateAttribute(): string
    {
        return number_format($this->prix, 2, ',', ' ') . ' €';
    }

    /**
     * Accesseur pour le membre avec fallback
     * Retourne le membre assigné ou null
     */
    public function getMembreAttribute(): ?EntrepriseMembre
    {
        return $this->membre()->first();
    }

    /**
     * Vérifie si la réservation est pour une cliente non inscrite
     */
    public function estPourClienteNonInscrite(): bool
    {
        return is_null($this->user_id);
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
     * Génère un hash unique pour la réservation
     * Format : {hash de la date}-{hash de l'id du membre}-{hash de l'id de l'entreprise}
     * + un élément aléatoire pour garantir l'unicité
     */
    public function generateHash(): string
    {
        // Hash de la date (timestamp format YmdHis)
        $dateTimestamp = $this->date_reservation ? $this->date_reservation->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s');
        $dateHash = substr(hash('sha256', $dateTimestamp . config('app.key')), 0, 8);
        
        // Hash de l'ID du membre (ou 0 si pas de membre)
        $membreId = $this->membre_id ?? 0;
        $membreHash = substr(hash('sha256', $membreId . config('app.key')), 0, 8);
        
        // Hash de l'ID de l'entreprise
        $entrepriseHash = substr(hash('sha256', ($this->entreprise_id ?? 0) . config('app.key')), 0, 8);
        
        // Ajouter un élément unique (microtime + random + ID si disponible) pour garantir l'unicité même avec mêmes données
        $uniquePart = substr(hash('sha256', microtime(true) . Str::random(16) . ($this->id ?? Str::random(8))), 0, 8);
        
        return $dateHash . '-' . $membreHash . '-' . $entrepriseHash . '-' . $uniquePart;
    }

    /**
     * Boot method pour générer automatiquement le hash lors de la création
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reservation) {
            if (empty($reservation->hash)) {
                // Générer un hash unique
                do {
                    $reservation->hash = $reservation->generateHash();
                } while (static::where('hash', $reservation->hash)->exists());
            }
        });
    }

    /**
     * Génère un alias court à partir du hash complet
     * Format : 2 premières lettres de chaque sous-hash
     * Exemple : acaae2a7-e52fc00b-fce06b2d-6d5d42f1 -> ace5fc6d
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

    /**
     * Génère un alias court depuis un hash complet
     */
    public static function generateHashAlias(string $hash): ?string
    {
        $parts = explode('-', $hash);
        if (count($parts) !== 4) {
            return null;
        }

        $alias = '';
        foreach ($parts as $part) {
            $alias .= substr($part, 0, 2);
        }

        return $alias;
    }

    /**
     * Trouve une réservation par son hash (complet ou alias court)
     * L'alias court est les 2 premières lettres de chaque sous-hash
     * Exemple : hash complet = acaae2a7-e52fc00b-fce06b2d-6d5d42f1, alias = ace5fc6d
     */
    public static function findByHash(string $hash): ?self
    {
        // Si c'est un hash complet (contient des tirets et fait au moins 35 caractères)
        if (strpos($hash, '-') !== false && strlen($hash) >= 35) {
            return static::where('hash', $hash)->first();
        }

        // Sinon, c'est probablement un alias court (8 caractères)
        // On cherche toutes les réservations avec hash et on compare les alias
        if (strlen($hash) === 8) {
            // Récupérer toutes les réservations avec hash (chunk pour éviter la surcharge mémoire)
            $reservations = static::whereNotNull('hash')
                ->select('id', 'hash')
                ->get();
            
            foreach ($reservations as $reservation) {
                $alias = static::generateHashAlias($reservation->hash);
                if ($alias === $hash) {
                    // Recharger la réservation complète avec toutes les relations
                    return static::with(['user', 'entreprise', 'typeService', 'membre'])->find($reservation->id);
                }
            }
        }

        return null;
    }
}
