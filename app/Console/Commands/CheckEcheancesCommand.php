<?php

namespace App\Console\Commands;

use App\Models\Echeance;
use App\Models\EntrepriseSubscription;
use App\Models\User;
use App\Services\CalculMontantDuService;
use App\Services\PremiumAccessService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckEcheancesCommand extends Command
{
    protected $signature = 'subscriptions:check-echeances
                            {--force : Créer même si une échéance existe déjà}
                            {--user-id= : Limiter à un ou plusieurs user_id (virgules)}';

    protected $description = 'Crée les échéances Stripe (Premium + options entreprise). Les abonnements manuels passent par subscriptions:generate-invoices.';

    public function handle(): int
    {
        $this->info('Vérification des échéances mensuelles...');
        $force = $this->option('force');
        $jour = (int) now()->day;
        $dernierJourDuMois = (int) now()->daysInMonth;
        $estDernierJour = ($jour === $dernierJourDuMois);

        $created = 0;
        $skipped = 0;

        if ($estDernierJour && $dernierJourDuMois < 31) {
            $this->info("Dernier jour du mois ({$dernierJourDuMois}) : les jour_facturation > {$dernierJourDuMois} seront aussi traités.");
        }

        $missingJour = User::query()
            ->where('payment_provider', Echeance::PROVIDER_STRIPE)
            ->whereNotNull('premium_actif_jusqu')
            ->whereNull('jour_facturation')
            ->when($this->scopedUserIds() !== [], fn ($q) => $q->whereIn('id', $this->scopedUserIds()))
            ->pluck('id');

        if ($missingJour->isNotEmpty()) {
            $this->warn('jour_facturation manquant, aucune échéance Premium : '.$missingJour->implode(', '));
            Log::warning('check-echeances: jour_facturation manquant', [
                'user_ids' => $missingJour->all(),
            ]);
        }

        $usersAuto = User::query()
            ->where('payment_provider', Echeance::PROVIDER_STRIPE)
            ->whereNotNull('jour_facturation')
            ->whereNotNull('premium_actif_jusqu')
            ->where(function ($q) use ($jour, $dernierJourDuMois, $estDernierJour) {
                $q->where('jour_facturation', $jour);
                if ($estDernierJour) {
                    $q->orWhere('jour_facturation', '>', $dernierJourDuMois);
                }
            })
            ->where(function ($q) {
                $q->where('abonnement_manuel', false)->orWhereNull('abonnement_manuel');
            })
            ->when($this->scopedUserIds() !== [], fn ($q) => $q->whereIn('id', $this->scopedUserIds()))
            ->get();

        foreach ($usersAuto as $user) {
            if (! PremiumAccessService::isEligibleForStripeCron($user)) {
                $skipped++;
                continue;
            }

            [$debut, $fin] = PremiumAccessService::nextAnniversaryPeriod($user);

            $exists = Echeance::where('user_id', $user->id)
                ->whereNull('entreprise_id')
                ->where('subscription_type', Echeance::TYPE_DEFAULT)
                ->where('payment_origin', Echeance::ORIGIN_AUTO_CARD)
                ->whereDate('periode_debut', $debut)
                ->whereDate('periode_fin', $fin)
                ->whereNotIn('statut', [Echeance::STATUT_ANNULE, Echeance::STATUT_ARRETE])
                ->exists();
            if ($exists && ! $force) {
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

            $echeance = Echeance::updateOrCreate(
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
                    'jour_facturation' => $user->jour_facturation,
                    'montant_du' => $calc['montant_du'],
                    'montant_final' => $calc['montant_final'],
                    'reduction_promo' => $calc['reduction_promo'],
                    'promo_code_id' => $calc['promo_code_id'],
                    'statut' => Echeance::STATUT_A_PAYER,
                    'metadata' => ['lignes' => $calc['lignes'], 'created_by_scheduler' => true],
                ]
            );
            $echeance->setRelation('user', $user);
            PremiumAccessService::applyGrace($echeance);
            $created++;
        }

        $subs = EntrepriseSubscription::query()
            ->where('est_manuel', false)
            ->where(function ($q) {
                $q->whereNull('payment_provider')
                    ->orWhere('payment_provider', '!=', 'play');
            })
            ->whereNotNull('jour_renouvellement')
            ->where(function ($q) use ($jour, $dernierJourDuMois, $estDernierJour) {
                $q->where('jour_renouvellement', $jour);
                if ($estDernierJour) {
                    $q->orWhere('jour_renouvellement', '>', $dernierJourDuMois);
                }
            })
            ->with('entreprise.user')
            ->when($this->scopedUserIds() !== [], function ($q) {
                $q->whereHas('entreprise', fn ($eq) => $eq->whereIn('user_id', $this->scopedUserIds()));
            })
            ->get();

        foreach ($subs as $sub) {
            if (! $sub->estActif()) {
                $skipped++;
                continue;
            }

            $entreprise = $sub->entreprise;
            $user = $entreprise?->user;
            if (! $user) {
                $skipped++;
                continue;
            }

            $debut = now()->copy()->startOfMonth();
            $fin = now()->copy()->endOfMonth();

            $origin = Echeance::ORIGIN_AUTO_CARD;
            $exists = Echeance::where('user_id', $user->id)
                ->where('entreprise_id', $entreprise->id)
                ->where('subscription_type', $sub->type)
                ->where('payment_origin', $origin)
                ->whereDate('periode_debut', $debut)
                ->whereDate('periode_fin', $fin)
                ->whereNotIn('statut', [Echeance::STATUT_ANNULE, Echeance::STATUT_ARRETE])
                ->exists();
            if ($exists && ! $force) {
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
                    'jour_facturation' => $sub->jour_renouvellement,
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

    /**
     * @return list<int>
     */
    private function scopedUserIds(): array
    {
        $raw = (string) $this->option('user-id');
        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('intval', explode(',', $raw))));
    }
}
