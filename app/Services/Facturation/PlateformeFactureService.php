<?php

namespace App\Services\Facturation;

use App\Models\EntrepriseSubscription;
use App\Models\Facture;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PlateformeFactureService
{
    public function __construct(
        private DocumentSequenceService $sequences,
        private DocumentSnapshotService $snapshots,
    ) {}

    public function generateFromManualSubscription(User $user, ?Carbon $dateFacture = null): ?Facture
    {
        if (! $user->abonnement_manuel || ! $user->abonnement_manuel_montant) {
            return null;
        }

        $dateFacture = $dateFacture ?? now();
        [$periodeDebut, $periodeFin] = $this->periode(
            $dateFacture,
            $user->abonnement_manuel_type_renouvellement === 'annuel'
        );

        $existante = Facture::query()
            ->where('user_id', $user->id)
            ->where('type_facture', 'abonnement_manuel')
            ->whereBetween('date_facture', [$periodeDebut, $periodeFin])
            ->first();

        if ($existante) {
            return $this->figer($existante);
        }

        $montantHt = (float) $user->abonnement_manuel_montant;
        $periodeLibelle = $user->abonnement_manuel_type_renouvellement === 'mensuel' ? 'mensuel' : 'annuel';

        $facture = Facture::create([
            'user_id' => $user->id,
            'entreprise_id' => $user->entreprises()->first()?->id,
            'reservation_id' => null,
            'type_facture' => 'abonnement_manuel',
            'numero_facture' => $this->sequences->nextPlateforme(),
            'date_facture' => $dateFacture,
            'date_echeance' => $dateFacture->copy()->addDays(30),
            'montant_ht' => $montantHt,
            'taux_tva' => 0,
            'montant_tva' => 0,
            'montant_ttc' => $montantHt,
            'statut' => 'emise',
            'notes' => 'Facture d\'abonnement '.$periodeLibelle.' - Période du '.$periodeDebut->format('d/m/Y').' au '.$periodeFin->format('d/m/Y'),
        ]);

        Log::info('Facture d\'abonnement manuel générée', [
            'facture_id' => $facture->id,
            'user_id' => $user->id,
            'montant' => $montantHt,
        ]);

        return $this->figer($facture);
    }

    public function generateFromManualEntrepriseSubscription(EntrepriseSubscription $subscription, ?Carbon $dateFacture = null): ?Facture
    {
        if (! $subscription->est_manuel || ! $subscription->montant) {
            return null;
        }

        return $this->emettrePourAbonnementEntreprise(
            $subscription,
            $dateFacture ?? now(),
            (float) $subscription->montant,
            $subscription->type === 'site_web' ? 'Site Web Vitrine' : 'Gestion Multi-Personnes',
            $subscription->type_renouvellement === 'mensuel' ? 'mensuel' : 'annuel',
        );
    }

    public function generateFromStripeEntrepriseSubscription(EntrepriseSubscription $subscription, ?Carbon $dateFacture = null): ?Facture
    {
        if ($subscription->est_manuel || ! $subscription->stripe_id) {
            return null;
        }

        $montantHt = (float) ($subscription->montant ?? 0);

        if ($montantHt == 0 && $subscription->stripe_price) {
            try {
                $stripePrice = \Stripe\Price::retrieve($subscription->stripe_price, ['api_key' => config('services.stripe.secret')]);
                $montantHt = $stripePrice->unit_amount / 100;
            } catch (\Exception $e) {
                Log::warning('Impossible de récupérer le prix Stripe pour la subscription '.$subscription->id.': '.$e->getMessage());
            }
        }

        if ($montantHt == 0) {
            Log::warning('Impossible de générer une facture pour la subscription '.$subscription->id.' : montant invalide');

            return null;
        }

        return $this->emettrePourAbonnementEntreprise(
            $subscription,
            $dateFacture ?? now(),
            $montantHt,
            'Stripe '.($subscription->type === 'site_web' ? 'Site Web Vitrine' : 'Gestion Multi-Personnes'),
            'mensuel',
        );
    }

    /**
     * Fige le snapshot émetteur Lucas Espinar EI sans renommer un numéro déjà attribué.
     */
    public function figer(Facture $facture): Facture
    {
        if (is_array($facture->snapshot) && ($facture->snapshot['numero'] ?? null)) {
            return $facture;
        }

        $facture->loadMissing(['entreprise', 'user', 'entrepriseSubscription']);
        $snapshot = $this->snapshots->pourAbonnementPlateforme($facture);

        $facture->forceFill([
            'snapshot' => $snapshot,
            'verrouillee_at' => $facture->verrouillee_at ?? now(),
        ])->saveQuietly();

        return $facture->fresh(['entreprise', 'user', 'entrepriseSubscription']) ?? $facture;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function periode(Carbon $dateFacture, bool $annuel): array
    {
        if ($annuel) {
            return [$dateFacture->copy()->startOfYear(), $dateFacture->copy()->endOfYear()];
        }

        return [$dateFacture->copy()->startOfMonth(), $dateFacture->copy()->endOfMonth()];
    }

    private function emettrePourAbonnementEntreprise(
        EntrepriseSubscription $subscription,
        Carbon $dateFacture,
        float $montantHt,
        string $libelleOffre,
        string $periodeLibelle,
    ): Facture {
        $subscription->loadMissing('entreprise');
        $entreprise = $subscription->entreprise;
        [$periodeDebut, $periodeFin] = $this->periode($dateFacture, $subscription->type_renouvellement === 'annuel');

        $existante = Facture::query()
            ->where('entreprise_subscription_id', $subscription->id)
            ->where('type_facture', 'abonnement_entreprise')
            ->whereBetween('date_facture', [$periodeDebut, $periodeFin])
            ->first();

        if ($existante) {
            return $this->figer($existante);
        }

        $facture = Facture::create([
            'user_id' => $entreprise->user_id,
            'entreprise_id' => $entreprise->id,
            'entreprise_subscription_id' => $subscription->id,
            'reservation_id' => null,
            'type_facture' => 'abonnement_entreprise',
            'numero_facture' => $this->sequences->nextPlateforme(),
            'date_facture' => $dateFacture,
            'date_echeance' => $dateFacture->copy()->addDays(30),
            'montant_ht' => $montantHt,
            'taux_tva' => 0,
            'montant_tva' => 0,
            'montant_ttc' => $montantHt,
            'statut' => 'emise',
            'notes' => 'Facture d\'abonnement '.$libelleOffre.' ('.$periodeLibelle.') - Période du '.$periodeDebut->format('d/m/Y').' au '.$periodeFin->format('d/m/Y'),
        ]);

        Log::info('Facture d\'abonnement entreprise générée', [
            'facture_id' => $facture->id,
            'entreprise_id' => $entreprise->id,
            'subscription_id' => $subscription->id,
            'montant' => $montantHt,
        ]);

        return $this->figer($facture);
    }
}
