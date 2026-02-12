<?php

namespace App\Console\Commands;

use App\Models\Echeance;
use App\Models\EntrepriseSubscription;
use App\Models\User;
use App\Services\CalculMontantDuService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckEcheancesCommand extends Command
{
    protected $signature = 'subscriptions:check-echeances {--force : Créer même si une échéance existe déjà}';

    protected $description = 'Crée les échéances mensuelles (Premium + options entreprise) selon les jours de facturation';

    public function handle(): int
    {
        $this->info('Vérification des échéances mensuelles...');
        $force = $this->option('force');
        $jour = now()->day;
        $dernierJourDuMois = now()->daysInMonth;
        $estDernierJour = ($jour === $dernierJourDuMois);
        $debut = now()->copy()->startOfMonth();
        $fin = now()->copy()->endOfMonth();

        $created = 0;
        $skipped = 0;

        if ($estDernierJour && $dernierJourDuMois < 31) {
            $this->info("Dernier jour du mois ({$dernierJourDuMois}) : les jour_facturation > {$dernierJourDuMois} seront aussi traités.");
        }

        // --- Échéances Premium (jour_facturation user) ---
        // Fix fin de mois : le 28 fév, on traite aussi jour_facturation = 29, 30, 31
        $users = User::query()
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

        foreach ($users as $user) {
            if ($user->abonnement_manuel && $user->abonnement_manuel_actif_jusqu && $user->abonnement_manuel_actif_jusqu->isFuture()) {
                $skipped++;
                continue;
            }

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
                ->whereDate('periode_debut', $debut)
                ->whereDate('periode_fin', $fin)
                ->whereIn('statut', [Echeance::STATUT_A_PAYER, Echeance::STATUT_PAYE])
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
                    'periode_debut' => $debut,
                    'periode_fin' => $fin,
                ],
                [
                    'jour_facturation' => $user->jour_facturation ?? 1,
                    'montant_du' => $calc['montant_du'],
                    'montant_final' => $calc['montant_final'],
                    'reduction_promo' => $calc['reduction_promo'],
                    'promo_code_id' => $calc['promo_code_id'],
                    'statut' => Echeance::STATUT_A_PAYER,
                    'metadata' => ['lignes' => $calc['lignes']],
                ]
            );
            $created++;
        }

        // --- Échéances options entreprise (jour_renouvellement) ---
        // Fix fin de mois : le 28 fév, on traite aussi jour_renouvellement = 29, 30, 31
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

            $exists = Echeance::where('user_id', $user->id)
                ->where('entreprise_id', $entreprise->id)
                ->where('subscription_type', $sub->type)
                ->whereDate('periode_debut', $debut)
                ->whereDate('periode_fin', $fin)
                ->whereIn('statut', [Echeance::STATUT_A_PAYER, Echeance::STATUT_PAYE])
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
                    'periode_debut' => $debut,
                    'periode_fin' => $fin,
                ],
                [
                    'jour_facturation' => $sub->jour_renouvellement ?? 1,
                    'montant_du' => $calc['montant_du'],
                    'montant_final' => $calc['montant_final'],
                    'reduction_promo' => $calc['reduction_promo'],
                    'promo_code_id' => $calc['promo_code_id'],
                    'statut' => Echeance::STATUT_A_PAYER,
                    'metadata' => ['lignes' => $calc['lignes']],
                ]
            );
            $created++;
        }

        $this->info("Terminé. Créées : {$created}, ignorés : {$skipped}.");
        return 0;
    }
}
