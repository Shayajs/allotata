<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
}
