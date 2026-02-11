<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Echeance extends Model
{
    use HasFactory;

    public const STATUT_A_PAYER = 'a_payer';
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_PAYE = 'paye';
    public const STATUT_ECHEC = 'echec';
    public const STATUT_ANNULE = 'annule';
    public const STATUT_ARRETE = 'arrete';

    public const TYPE_DEFAULT = 'default';
    public const TYPE_SITE_WEB = 'site_web';
    public const TYPE_MULTI_PERSONNES = 'multi_personnes';

    protected $fillable = [
        'user_id',
        'entreprise_id',
        'subscription_type',
        'periode_debut',
        'periode_fin',
        'jour_facturation',
        'montant_du',
        'montant_final',
        'reduction_promo',
        'reduction_manuel',
        'reduction_manuel_notes',
        'promo_code_id',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'paye_at',
        'statut',
        'metadata',
        'facture_id',
    ];

    protected function casts(): array
    {
        return [
            'periode_debut' => 'date',
            'periode_fin' => 'date',
            'montant_du' => 'decimal:2',
            'montant_final' => 'decimal:2',
            'reduction_promo' => 'decimal:2',
            'reduction_manuel' => 'decimal:2',
            'paye_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class, 'promo_code_id');
    }

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class);
    }

    public function estPayee(): bool
    {
        return $this->statut === self::STATUT_PAYE;
    }

    public function estAPayer(): bool
    {
        return $this->statut === self::STATUT_A_PAYER || $this->statut === self::STATUT_EN_ATTENTE;
    }

    public function estArrete(): bool
    {
        return $this->statut === self::STATUT_ARRETE;
    }

    /**
     * La période couvre-t-elle la date donnée ?
     */
    public function couvre(\Carbon\Carbon $date): bool
    {
        return $date->between($this->periode_debut, $this->periode_fin);
    }

    /**
     * Libellé court pour affichage (Premium, Site Web – X, Multi-Personnes – X).
     */
    public function libelle(): string
    {
        if ($this->subscription_type === self::TYPE_DEFAULT || !$this->entreprise_id) {
            return 'Abonnement Premium';
        }
        $ent = $this->entreprise;
        $nom = $ent ? $ent->nom : '?';
        if ($this->subscription_type === self::TYPE_SITE_WEB) {
            return 'Site Web – ' . $nom;
        }
        if ($this->subscription_type === self::TYPE_MULTI_PERSONNES) {
            return 'Multi-Personnes – ' . $nom;
        }
        return $this->subscription_type;
    }

    /**
     * Montant à payer après promo + réduction manuelle.
     */
    public function montantApresReductions(): float
    {
        $base = (float) $this->montant_du;
        $promo = (float) ($this->reduction_promo ?? 0);
        $manuel = (float) ($this->reduction_manuel ?? 0);
        return max(0, $base - $promo - $manuel);
    }
}
