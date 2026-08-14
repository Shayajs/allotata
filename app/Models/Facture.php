<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Facture extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'entreprise_id',
        'user_id',
        'entreprise_subscription_id',
        'type_facture',
        'numero_facture',
        'date_facture',
        'date_echeance',
        'montant_ht',
        'taux_tva',
        'montant_tva',
        'montant_ttc',
        'statut',
        'notes',
        'snapshot',
        'date_paiement',
        'verrouillee_at',
    ];

    protected function casts(): array
    {
        return [
            'date_facture' => 'date',
            'date_echeance' => 'date',
            'montant_ht' => 'decimal:2',
            'taux_tva' => 'decimal:2',
            'montant_tva' => 'decimal:2',
            'montant_ttc' => 'decimal:2',
            'snapshot' => 'array',
            'date_paiement' => 'date',
            'verrouillee_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Facture $facture) {
            if (! $facture->verrouillee_at) {
                return;
            }

            $interdit = [
                'numero_facture', 'montant_ht', 'montant_tva', 'montant_ttc', 'taux_tva',
                'date_facture', 'reservation_id', 'entreprise_id', 'user_id', 'notes', 'type_facture',
            ];
            foreach ($interdit as $champ) {
                if ($facture->isDirty($champ)) {
                    throw new \App\Exceptions\ImmutableDocumentException;
                }
            }

            if ($facture->isDirty('statut')) {
                $from = $facture->getOriginal('statut');
                $to = $facture->statut;
                if (! ($from === 'emise' && $to === 'payee')) {
                    throw new \App\Exceptions\ImmutableDocumentException;
                }
            }

            if ($facture->isDirty('snapshot')) {
                $orig = $facture->getOriginal('snapshot') ?? [];
                $nouveau = $facture->snapshot ?? [];
                if (is_string($orig)) {
                    $orig = json_decode($orig, true) ?? [];
                }
                unset($orig['paiement'], $nouveau['paiement']);
                if ($orig != $nouveau) {
                    throw new \App\Exceptions\ImmutableDocumentException;
                }
            }
        });
    }

    public function estVerrouillee(): bool
    {
        return $this->verrouillee_at !== null;
    }

    public function estVisibleParClient(): bool
    {
        if (in_array($this->type_facture, ['abonnement_manuel', 'abonnement_entreprise'], true)) {
            return true;
        }

        return $this->statut === 'payee';
    }

    public function estAbonnementPlateforme(): bool
    {
        return in_array($this->type_facture, ['abonnement_manuel', 'abonnement_entreprise'], true);
    }

    public function libelleOrigine(): string
    {
        return $this->estAbonnementPlateforme() ? 'Abonnement Allotata' : 'Prestation';
    }

    /**
     * Relation : Une facture appartient à une réservation (pour compatibilité avec factures simples)
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    /**
     * Relation : Une facture peut avoir plusieurs réservations (factures groupées)
     */
    public function reservations(): BelongsToMany
    {
        return $this->belongsToMany(Reservation::class, 'facture_reservation')
            ->withTimestamps();
    }

    /**
     * Vérifie si la facture est groupée (plusieurs réservations)
     */
    public function estGroupee(): bool
    {
        return $this->reservations()->count() > 0;
    }

    /**
     * Relation : Une facture appartient à une entreprise
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * Relation : Une facture appartient à un client (User)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation : Une facture peut appartenir à un abonnement entreprise
     */
    public function entrepriseSubscription(): BelongsTo
    {
        return $this->belongsTo(EntrepriseSubscription::class);
    }

    /**
     * Génère un numéro de facture unique
     */
    public static function generateNumeroFacture(): string
    {
        $year = date('Y');
        $max = 0;

        foreach (self::where('numero_facture', 'like', "FAC-{$year}-______")->pluck('numero_facture') as $numero) {
            if (preg_match('/^FAC-'.$year.'-(\d{6})$/', (string) $numero, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return 'FAC-'.$year.'-'.str_pad((string) ($max + 1), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Génère une facture pour un abonnement manuel utilisateur
     */
    public static function generateFromManualSubscription(User $user, \Carbon\Carbon $dateFacture = null): ?Facture
    {
        return app(\App\Services\Facturation\PlateformeFactureService::class)
            ->generateFromManualSubscription($user, $dateFacture);
    }

    /**
     * Génère une facture pour un abonnement manuel entreprise
     */
    public static function generateFromManualEntrepriseSubscription(EntrepriseSubscription $subscription, \Carbon\Carbon $dateFacture = null): ?Facture
    {
        return app(\App\Services\Facturation\PlateformeFactureService::class)
            ->generateFromManualEntrepriseSubscription($subscription, $dateFacture);
    }

    /**
     * Génère une facture pour un abonnement Stripe entreprise
     */
    public static function generateFromStripeEntrepriseSubscription(EntrepriseSubscription $subscription, \Carbon\Carbon $dateFacture = null): ?Facture
    {
        return app(\App\Services\Facturation\PlateformeFactureService::class)
            ->generateFromStripeEntrepriseSubscription($subscription, $dateFacture);
    }

    /**
     * Génère une facture manuelle pour une entreprise ou un membre
     */
    public static function generateManualInvoice(array $data): ?Facture
    {
        $validated = [
            'entreprise_id' => $data['entreprise_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'montant_ht' => $data['montant_ht'] ?? 0,
            'taux_tva' => $data['taux_tva'] ?? 0,
            'date_facture' => $data['date_facture'] ?? now(),
            'date_echeance' => $data['date_echeance'] ?? now()->addDays(30),
            'notes' => $data['notes'] ?? null,
            'type_facture' => $data['type_facture'] ?? 'reservation',
            'statut' => $data['statut'] ?? 'emise',
        ];

        // Vérifier qu'au moins entreprise_id ou user_id est fourni
        if (!$validated['entreprise_id'] && !$validated['user_id']) {
            throw new \Exception('Au moins entreprise_id ou user_id doit être fourni');
        }

        // Calculer les montants
        $montantHT = $validated['montant_ht'];
        $tauxTVA = $validated['taux_tva'];
        $montantTVA = $montantHT * ($tauxTVA / 100);
        $montantTTC = $montantHT + $montantTVA;

        $estPlateforme = in_array($validated['type_facture'], ['abonnement_manuel', 'abonnement_entreprise'], true);
        $numero = $estPlateforme
            ? app(\App\Services\Facturation\DocumentSequenceService::class)->nextPlateforme()
            : self::generateNumeroFacture();

        // Créer la facture
        $facture = self::create([
            'reservation_id' => null,
            'entreprise_id' => $validated['entreprise_id'],
            'user_id' => $validated['user_id'],
            'entreprise_subscription_id' => $data['entreprise_subscription_id'] ?? null,
            'type_facture' => $validated['type_facture'],
            'numero_facture' => $numero,
            'date_facture' => $validated['date_facture'],
            'date_echeance' => $validated['date_echeance'],
            'montant_ht' => $montantHT,
            'taux_tva' => $tauxTVA,
            'montant_tva' => $montantTVA,
            'montant_ttc' => $montantTTC,
            'statut' => $validated['statut'],
            'notes' => $validated['notes'],
        ]);

        if ($estPlateforme) {
            $facture = app(\App\Services\Facturation\PlateformeFactureService::class)->figer($facture);
        }

        \Log::info('Facture manuelle générée', [
            'facture_id' => $facture->id,
            'entreprise_id' => $validated['entreprise_id'],
            'user_id' => $validated['user_id'],
            'type_facture' => $validated['type_facture'],
            'montant' => $montantTTC,
        ]);

        return $facture;
    }

    /**
     * Émet une facture figée à partir d'une réservation (prestation terminée).
     */
    public static function generateFromReservation(Reservation $reservation): ?Facture
    {
        return app(\App\Services\Facturation\FactureEmissionService::class)
            ->emettrePourReservation($reservation);
    }

    /**
     * Génère une facture groupée à partir de plusieurs réservations
     */
    public static function generateFromReservations(array $reservationIds, $entrepriseId, $userId, $tauxTVA = 0): ?Facture
    {
        if (empty($reservationIds)) {
            return null;
        }

        $reservations = Reservation::whereIn('id', $reservationIds)
            ->where('entreprise_id', $entrepriseId)
            ->where('user_id', $userId)
            ->where('est_paye', true)
            ->with(['entreprise'])
            ->get();

        if ($reservations->isEmpty()) {
            return null;
        }

        // Vérifier qu'aucune de ces réservations n'a déjà une facture (simple ou groupée)
        foreach ($reservations as $reservation) {
            if ($reservation->aDejaFacture()) {
                \Log::warning("La réservation #{$reservation->id} a déjà une facture");
                return null;
            }
        }

        $entreprise = $reservations->first()->entreprise;
        $emission = app(\App\Services\Facturation\FactureEmissionService::class);
        $emission->assertProfilComplet($entreprise);

        if ($tauxTVA <= 0 && $entreprise->assujetti_tva) {
            $tauxTVA = (float) ($entreprise->taux_tva_defaut ?? 20);
        } elseif (! $entreprise->assujetti_tva) {
            $tauxTVA = 0;
        }

        $montantHT = (float) $reservations->sum('prix');
        $montantTVA = round($montantHT * ($tauxTVA / 100), 2);
        $montantTTC = round($montantHT + $montantTVA, 2);
        $sequences = app(\App\Services\Facturation\DocumentSequenceService::class);

        $facture = self::create([
            'reservation_id' => null,
            'entreprise_id' => $entrepriseId,
            'user_id' => $userId,
            'type_facture' => 'reservation',
            'numero_facture' => $sequences->next($entrepriseId, \App\Services\Facturation\DocumentSequenceService::TYPE_FACTURE),
            'date_facture' => now(),
            'date_echeance' => now()->addDays(30),
            'montant_ht' => $montantHT,
            'taux_tva' => $tauxTVA,
            'montant_tva' => $montantTVA,
            'montant_ttc' => $montantTTC,
            'statut' => 'emise',
            'verrouillee_at' => now(),
        ]);

        $facture->reservations()->attach($reservationIds);
        $facture->load('user');

        $snapshots = app(\App\Services\Facturation\DocumentSnapshotService::class);
        $snapshot = $snapshots->pourFacture(
            $facture,
            $entreprise,
            $reservations,
            $facture->user,
            $reservations->first()
        );
        $facture->forceFill(['snapshot' => $snapshot])->saveQuietly();

        return $facture->fresh();
    }
}
