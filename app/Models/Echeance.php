<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Echeance extends Model
{
    use HasFactory;

    public const STATUT_BROUILLON = 'brouillon';   // Intention d'achat, pas encore payé (annulable)
    public const STATUT_A_PAYER = 'a_payer';       // Échéance due (générée par CRON pour renouvellement)
    public const STATUT_EN_ATTENTE = 'en_attente'; // Paiement en cours de traitement (3DS, SEPA…)
    public const STATUT_PAYE = 'paye';             // Paiement confirmé
    public const STATUT_ECHEC = 'echec';           // Paiement échoué (carte refusée, etc.)
    public const STATUT_ANNULE = 'annule';         // Annulé par l'utilisateur ou le système
    public const STATUT_ARRETE = 'arrete';         // Arrêté (abonnement résilié)
    public const STATUT_REMBOURSE = 'rembourse';   // Remboursé (total ou partiel)

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
        'stripe_refund_id',
        'refund_amount',
        'refund_status',
        'refund_reason',
        'refund_notes',
        'refunded_by',
        'refunded_at',
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
            'refund_amount' => 'decimal:2',
            'refunded_at' => 'datetime',
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

    public function estBrouillon(): bool
    {
        return $this->statut === self::STATUT_BROUILLON;
    }

    public function estEchec(): bool
    {
        return $this->statut === self::STATUT_ECHEC;
    }

    public function estArrete(): bool
    {
        return $this->statut === self::STATUT_ARRETE;
    }

    /**
     * L'échéance est-elle réglable ? (bouton "Régler" / "Régulariser")
     */
    public function estReglable(): bool
    {
        return in_array($this->statut, [
            self::STATUT_BROUILLON,
            self::STATUT_A_PAYER,
            self::STATUT_ECHEC,
        ], true);
    }

    /**
     * L'échéance est-elle annulable par l'utilisateur ?
     */
    public function estAnnulable(): bool
    {
        return in_array($this->statut, [
            self::STATUT_BROUILLON,
            self::STATUT_A_PAYER,
            self::STATUT_EN_ATTENTE,
        ], true);
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

    public function estRemboursee(): bool
    {
        return $this->statut === self::STATUT_REMBOURSE;
    }

    public function estRemboursable(): bool
    {
        return $this->estPayee()
            && $this->stripe_payment_intent_id
            && !$this->stripe_refund_id;
    }

    public function estPartielementRemboursee(): bool
    {
        return $this->estPayee()
            && $this->stripe_refund_id
            && $this->refund_amount
            && $this->refund_amount < ($this->montant_final ?? $this->montant_du);
    }

    public function refundedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
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
