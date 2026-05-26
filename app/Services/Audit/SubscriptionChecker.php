<?php

namespace App\Services\Audit;

use App\Models\EntrepriseSubscription;
use App\Models\Echeance;
use App\Models\User;

class SubscriptionChecker extends BaseChecker
{
    public function key(): string
    {
        return 'subscriptions';
    }

    public function label(): string
    {
        return 'Abonnements';
    }

    public function run(): array
    {
        $items = [];
        $recommendations = [];
        $score = 100;

        // Abonnements entreprise
        $allSubs = EntrepriseSubscription::all();
        $activeSubs = $allSubs->filter(fn ($s) => $s->estActif())->count();
        $expiredSubs = $allSubs->count() - $activeSubs;
        $trialSubs = $allSubs->filter(fn ($s) => $s->estEnEssai())->count();

        $items[] = ['label' => 'Abonnements entreprise actifs', 'value' => $activeSubs, 'severity' => 'info'];
        $items[] = ['label' => 'Abonnements expirés', 'value' => $expiredSubs, 'severity' => $expiredSubs > 10 ? 'warning' : 'ok'];
        $items[] = ['label' => 'En période d\'essai', 'value' => $trialSubs, 'severity' => 'info'];

        // Churn rate (30j)
        $cancelledRecently = EntrepriseSubscription::whereNotNull('ends_at')
            ->where('ends_at', '>=', now()->subDays(30))
            ->where('ends_at', '<=', now())
            ->count();
        $churnRate = $activeSubs > 0 ? round(($cancelledRecently / ($activeSubs + $cancelledRecently)) * 100, 1) : 0;
        $items[] = ['label' => 'Taux de churn (30j)', 'value' => $churnRate . '%', 'severity' => $churnRate > 10 ? 'critical' : ($churnRate > 5 ? 'warning' : 'ok')];
        if ($churnRate > 10) {
            $score -= 15;
            $recommendations[] = "Taux de churn élevé ({$churnRate}%) — analyser les raisons de désabonnement.";
        }

        // Échéances impayées
        $unpaidEcheances = Echeance::whereIn('statut', ['echec', 'a_payer'])
            ->where('periode_fin', '<', now())
            ->count();
        $items[] = ['label' => 'Échéances impayées', 'value' => $unpaidEcheances, 'severity' => $unpaidEcheances > 10 ? 'critical' : ($unpaidEcheances > 3 ? 'warning' : 'ok')];
        $score -= min(20, $unpaidEcheances * 2);

        if ($unpaidEcheances > 5) {
            $recommendations[] = "Beaucoup d'échéances impayées ({$unpaidEcheances}) — relancer les paiements.";
        }

        // Abonnements Stripe désynchronisés
        $stripeSubs = EntrepriseSubscription::whereNotNull('stripe_id')
            ->where('stripe_status', '!=', 'active')
            ->where('stripe_status', '!=', 'canceled')
            ->whereNull('ends_at')
            ->count();
        $items[] = ['label' => 'Abonnements Stripe désynchronisés', 'value' => $stripeSubs, 'severity' => $stripeSubs > 3 ? 'critical' : ($stripeSubs > 0 ? 'warning' : 'ok')];
        if ($stripeSubs > 0) {
            $score -= min(15, $stripeSubs * 5);
            $recommendations[] = "Des abonnements Stripe semblent désynchronisés — lancer la réconciliation.";
        }

        // Abonnements manuels expirant bientôt
        $expiringManual = EntrepriseSubscription::where('est_manuel', true)
            ->whereNotNull('actif_jusqu')
            ->whereBetween('actif_jusqu', [now(), now()->addDays(7)])
            ->count();
        $items[] = ['label' => 'Manuels expirant sous 7j', 'value' => $expiringManual, 'severity' => $expiringManual > 0 ? 'warning' : 'ok'];
        if ($expiringManual > 0) {
            $recommendations[] = "{$expiringManual} abonnement(s) manuel(s) expirent dans les 7 prochains jours.";
        }

        // Revenus récurrents mensuels (MRR estimation)
        $mrr = Echeance::where('statut', 'paye')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('montant_du');
        $items[] = ['label' => 'MRR estimé', 'value' => number_format($mrr, 2, ',', ' ') . ' €', 'severity' => 'info'];

        return $this->result($score, $items, $recommendations);
    }
}
