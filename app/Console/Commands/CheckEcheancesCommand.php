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

    protected $description = 'Crée les échéances mensuelles (Premium + options entreprise) selon les jours de facturation';

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
            $hasStripe = $user->subscribed('default');
            $hasEcheancePayee = Echeance::where('user_id', $user->id)
                ->where('statut', Echeance::STATUT_PAYE)
                ->where('paye_at', '>=', now()->subMonths(12))
                ->exists();
            if (!$hasStripe && !$hasEcheancePayee) {
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

        // --- Échéances Premium manuelles (visibles membre + admin, jamais auto-charge) ---
        $usersManual = User::query()
            ->where('abonnement_manuel', true)
            ->whereNotNull('abonnement_manuel_actif_jusqu')
            ->get();

        foreach ($usersManual as $user) {
            if (!$user->abonnement_manuel_actif_jusqu || $user->abonnement_manuel_actif_jusqu->isPast()) {
                $skipped++;
                continue;
            }

            $manualBillingDay = (int) ($user->abonnement_manuel_jour_renouvellement ?: ($user->abonnement_manuel_date_debut?->day ?? $user->jour_facturation ?? 1));
            if (!$this->shouldRunForDay($manualBillingDay, $jour, $dernierJourDuMois, $estDernierJour)) {
                $skipped++;
                continue;
            }

            $exists = Echeance::where('user_id', $user->id)
                ->whereNull('entreprise_id')
                ->where('subscription_type', Echeance::TYPE_DEFAULT)
                ->where('payment_origin', Echeance::ORIGIN_MANUAL)
                ->whereDate('periode_debut', $debut)
                ->whereDate('periode_fin', $fin)
                ->whereNotIn('statut', [Echeance::STATUT_ANNULE, Echeance::STATUT_ARRETE])
                ->exists();
            if ($exists && !$force) {
                $skipped++;
                continue;
            }

            $manualAmount = (float) ($user->abonnement_manuel_montant ?? 0);
            if ($manualAmount <= 0) {
                $manualAmount = (float) (\App\Models\Tarif::displayForUser($user, Echeance::TYPE_DEFAULT)['amount'] ?? 0);
            }
            if ($manualAmount <= 0) {
                $skipped++;
                continue;
            }

            Echeance::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'entreprise_id' => null,
                    'subscription_type' => Echeance::TYPE_DEFAULT,
                    'payment_origin' => Echeance::ORIGIN_MANUAL,
                    'periode_debut' => $debut,
                    'periode_fin' => $fin,
                ],
                [
                    'payment_provider' => null,
                    'auto_charge_eligible' => false,
                    'jour_facturation' => $manualBillingDay,
                    'montant_du' => $manualAmount,
                    'montant_final' => $manualAmount,
                    'reduction_promo' => 0,
                    'promo_code_id' => null,
                    'statut' => Echeance::STATUT_A_PAYER,
                    'metadata' => array_filter([
                        'manual' => true,
                        'manual_notes' => $user->abonnement_manuel_notes,
                        'created_by_scheduler' => true,
                    ]),
                ]
            );
            $created++;
        }

        // --- Échéances options entreprise (auto + manuel) ---
        $subs = EntrepriseSubscription::query()
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
            if (!$sub->estActif()) {
                $skipped++;
                continue;
            }
            $entreprise = $sub->entreprise;
            $user = $entreprise?->user;
            if (!$user) {
                $skipped++;
                continue;
            }

            $origin = $sub->est_manuel ? Echeance::ORIGIN_MANUAL : Echeance::ORIGIN_AUTO_CARD;
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
                    'payment_provider' => $sub->est_manuel ? null : Echeance::PROVIDER_STRIPE,
                    'auto_charge_eligible' => !$sub->est_manuel,
                    'jour_facturation' => $sub->jour_renouvellement ?? 1,
                    'montant_du' => $calc['montant_du'],
                    'montant_final' => $calc['montant_final'],
                    'reduction_promo' => $calc['reduction_promo'],
                    'promo_code_id' => $calc['promo_code_id'],
                    'statut' => Echeance::STATUT_A_PAYER,
                    'metadata' => array_filter([
                        'lignes' => $calc['lignes'],
                        'manual' => (bool) $sub->est_manuel,
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
