<?php

namespace App\Console\Commands;

use App\Models\Echeance;
use App\Models\EntrepriseSubscription;
use App\Models\User;
use App\Services\CalculMontantDuService;
use Illuminate\Console\Command;

class CheckEcheancesCommand extends Command
{
    protected $signature = 'subscriptions:check-echeances {--force : Créer même si une échéance existe déjà}';

    protected $description = 'Crée les échéances Stripe (Premium + options entreprise). Les abonnements manuels passent par subscriptions:generate-invoices.';

    public function handle(): int
    {
        $this->info('Vérification des échéances mensuelles...');
        $force = $this->option('force');
        $jour = (int) now()->day;
        $dernierJourDuMois = (int) now()->daysInMonth;
        $estDernierJour = ($jour === $dernierJourDuMois);
        $debut = now()->copy()->startOfMonth();
        $fin = now()->copy()->endOfMonth();

        $created = 0;
        $skipped = 0;

        if ($estDernierJour && $dernierJourDuMois < 31) {
            $this->info("Dernier jour du mois ({$dernierJourDuMois}) : les jour_facturation > {$dernierJourDuMois} seront aussi traités.");
        }

        // --- Échéances Premium auto (carte / provider) ---
        $usersAuto = User::query()
            ->whereNotNull('jour_facturation')
            ->where(function ($q) use ($jour, $dernierJourDuMois, $estDernierJour) {
                $q->where('jour_facturation', $jour);
                if ($estDernierJour) {
                    $q->orWhere('jour_facturation', '>', $dernierJourDuMois);
                }
            })
            ->where(function ($q) {
                $q->where('abonnement_manuel', false)->orWhereNull('abonnement_manuel');
            })
            ->get();

        foreach ($usersAuto as $user) {
            // Uniquement abonnement Stripe actif (ou période de grâce Cashier) — pas de relance sur ancien paiement isolé.
            if (! $user->subscribed('default')) {
                $skipped++;
                continue;
            }

            $exists = Echeance::where('user_id', $user->id)
                ->whereNull('entreprise_id')
                ->where('subscription_type', Echeance::TYPE_DEFAULT)
                ->where('payment_origin', Echeance::ORIGIN_AUTO_CARD)
                ->whereDate('periode_debut', $debut)
                ->whereDate('periode_fin', $fin)
                ->whereNotIn('statut', [Echeance::STATUT_ANNULE, Echeance::STATUT_ARRETE])
                ->exists();
            if ($exists && !$force) {
                $skipped++;
                continue;
            }

            $tmp = new Echeance([
                'user_id' => $user->id,
                'entreprise_id' => null,
                'subscription_type' => Echeance::TYPE_DEFAULT,
                'periode_debut' => $debut,
                'periode_fin' => $fin,
                'reduction_manuel' => 0,
            ]);
            $tmp->setRelation('user', $user);
            $calc = CalculMontantDuService::calculerPourEcheance($tmp);
            if ($calc['montant_du'] <= 0) {
                $skipped++;
                continue;
            }

            Echeance::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'entreprise_id' => null,
                    'subscription_type' => Echeance::TYPE_DEFAULT,
                    'payment_origin' => Echeance::ORIGIN_AUTO_CARD,
                    'periode_debut' => $debut,
                    'periode_fin' => $fin,
                ],
                [
                    'payment_provider' => Echeance::PROVIDER_STRIPE,
                    'auto_charge_eligible' => true,
                    'jour_facturation' => $user->jour_facturation ?? 1,
                    'montant_du' => $calc['montant_du'],
                    'montant_final' => $calc['montant_final'],
                    'reduction_promo' => $calc['reduction_promo'],
                    'promo_code_id' => $calc['promo_code_id'],
                    'statut' => Echeance::STATUT_A_PAYER,
                    'metadata' => ['lignes' => $calc['lignes'], 'created_by_scheduler' => true],
                ]
            );
            $created++;
        }

        // --- Échéances options entreprise (Stripe uniquement ; manuel → subscriptions:generate-invoices) ---
        $subs = EntrepriseSubscription::query()
            ->where('est_manuel', false)
            ->whereNotNull('jour_renouvellement')
            ->where(function ($q) use ($jour, $dernierJourDuMois, $estDernierJour) {
                $q->where('jour_renouvellement', $jour);
                if ($estDernierJour) {
                    $q->orWhere('jour_renouvellement', '>', $dernierJourDuMois);
                }
            })
            ->with('entreprise.user')
            ->get();

        foreach ($subs as $sub) {
            if (! $sub->estActif()) {
                $skipped++;
                continue;
            }

            $entreprise = $sub->entreprise;
            $user = $entreprise?->user;
            if (!$user) {
                $skipped++;
                continue;
            }

            $origin = Echeance::ORIGIN_AUTO_CARD;
            $exists = Echeance::where('user_id', $user->id)
                ->where('entreprise_id', $entreprise->id)
                ->where('subscription_type', $sub->type)
                ->where('payment_origin', $origin)
                ->whereDate('periode_debut', $debut)
                ->whereDate('periode_fin', $fin)
                ->whereNotIn('statut', [Echeance::STATUT_ANNULE, Echeance::STATUT_ARRETE])
                ->exists();
            if ($exists && !$force) {
                $skipped++;
                continue;
            }

            $tmp = new Echeance([
                'user_id' => $user->id,
                'entreprise_id' => $entreprise->id,
                'subscription_type' => $sub->type,
                'periode_debut' => $debut,
                'periode_fin' => $fin,
                'reduction_manuel' => 0,
            ]);
            $tmp->setRelation('user', $user);
            $calc = CalculMontantDuService::calculerPourEcheance($tmp);
            if ($calc['montant_du'] <= 0) {
                $skipped++;
                continue;
            }

            Echeance::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'entreprise_id' => $entreprise->id,
                    'subscription_type' => $sub->type,
                    'payment_origin' => $origin,
                    'periode_debut' => $debut,
                    'periode_fin' => $fin,
                ],
                [
                    'payment_provider' => Echeance::PROVIDER_STRIPE,
                    'auto_charge_eligible' => true,
                    'jour_facturation' => $sub->jour_renouvellement ?? 1,
                    'montant_du' => $calc['montant_du'],
                    'montant_final' => $calc['montant_final'],
                    'reduction_promo' => $calc['reduction_promo'],
                    'promo_code_id' => $calc['promo_code_id'],
                    'statut' => Echeance::STATUT_A_PAYER,
                    'metadata' => array_filter([
                        'lignes' => $calc['lignes'],
                        'created_by_scheduler' => true,
                    ]),
                ]
            );
            $created++;
        }

        $this->info("Terminé. Créées : {$created}, ignorés : {$skipped}.");
        return 0;
    }

    private function shouldRunForDay(int $billingDay, int $today, int $daysInMonth, bool $isMonthEnd): bool
    {
        if ($billingDay === $today) {
            return true;
        }

        return $isMonthEnd && $billingDay > $daysInMonth;
    }
}
