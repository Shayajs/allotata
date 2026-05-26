<?php

namespace App\Services\Audit;

use App\Models\StripeTransaction;
use App\Models\Echeance;
use App\Models\EntrepriseSubscription;
use App\Models\PaymentAuditLog;

class StripeChecker extends BaseChecker
{
    public function key(): string
    {
        return 'stripe';
    }

    public function label(): string
    {
        return 'Stripe & Paiements';
    }

    public function run(): array
    {
        $items = [];
        $recommendations = [];
        $score = 100;

        // Transactions échouées (30 derniers jours)
        $failedTransactions = StripeTransaction::where('created_at', '>=', now()->subDays(30))
            ->where('status', 'failed')
            ->count();
        $totalTransactions = StripeTransaction::where('created_at', '>=', now()->subDays(30))->count();
        $failRate = $totalTransactions > 0 ? round(($failedTransactions / $totalTransactions) * 100, 1) : 0;

        $items[] = ['label' => 'Transactions échouées (30j)', 'value' => $failedTransactions, 'severity' => $failedTransactions > 10 ? 'critical' : ($failedTransactions > 3 ? 'warning' : 'ok')];
        $items[] = ['label' => 'Taux d\'échec', 'value' => $failRate . '%', 'severity' => $failRate > 10 ? 'critical' : ($failRate > 5 ? 'warning' : 'ok')];
        $score -= min(20, $failedTransactions * 2);

        // Échéances impayées
        $unpaidEcheances = Echeance::where('statut', 'echec')->where('created_at', '>=', now()->subDays(30))->count();
        $overdueEcheances = Echeance::where('statut', 'a_payer')
            ->where('periode_fin', '<', now())
            ->count();
        $items[] = ['label' => 'Échéances en échec (30j)', 'value' => $unpaidEcheances, 'severity' => $unpaidEcheances > 5 ? 'critical' : ($unpaidEcheances > 2 ? 'warning' : 'ok')];
        $items[] = ['label' => 'Échéances en retard', 'value' => $overdueEcheances, 'severity' => $overdueEcheances > 10 ? 'critical' : ($overdueEcheances > 3 ? 'warning' : 'ok')];
        $score -= min(15, $unpaidEcheances * 3);
        $score -= min(15, $overdueEcheances * 2);

        // Webhooks non traités
        $unprocessedWebhooks = StripeTransaction::where('processed', false)
            ->where('created_at', '<', now()->subHours(1))
            ->count();
        $items[] = ['label' => 'Webhooks non traités (>1h)', 'value' => $unprocessedWebhooks, 'severity' => $unprocessedWebhooks > 5 ? 'critical' : ($unprocessedWebhooks > 0 ? 'warning' : 'ok')];
        if ($unprocessedWebhooks > 5) {
            $score -= 10;
            $recommendations[] = 'Des webhooks Stripe ne sont pas traités — vérifier le queue worker.';
        }

        // Abonnements entreprise actifs
        $activeSubscriptions = EntrepriseSubscription::get()->filter(fn ($s) => $s->estActif())->count();
        $expiredNotManaged = EntrepriseSubscription::where('stripe_status', 'past_due')->count();
        $items[] = ['label' => 'Abonnements actifs', 'value' => $activeSubscriptions, 'severity' => 'ok'];
        $items[] = ['label' => 'Abonnements en impayé (past_due)', 'value' => $expiredNotManaged, 'severity' => $expiredNotManaged > 3 ? 'critical' : ($expiredNotManaged > 0 ? 'warning' : 'ok')];
        $score -= min(10, $expiredNotManaged * 3);

        // Clés Stripe configurées
        $stripeKey = config('services.stripe.key');
        $stripeSecret = config('services.stripe.secret');
        $webhookSecret = config('services.stripe.webhook.secret') ?? config('cashier.webhook.secret');
        $items[] = ['label' => 'Clé publique Stripe', 'value' => !empty($stripeKey) ? 'Configurée' : 'Manquante', 'severity' => !empty($stripeKey) ? 'ok' : 'critical'];
        $items[] = ['label' => 'Clé secrète Stripe', 'value' => !empty($stripeSecret) ? 'Configurée' : 'Manquante', 'severity' => !empty($stripeSecret) ? 'ok' : 'critical'];
        $items[] = ['label' => 'Webhook secret', 'value' => !empty($webhookSecret) ? 'Configuré' : 'Manquant', 'severity' => !empty($webhookSecret) ? 'ok' : 'warning'];

        if (empty($stripeKey) || empty($stripeSecret)) {
            $score -= 25;
            $recommendations[] = 'Clés Stripe manquantes — les paiements ne fonctionneront pas.';
        }

        // Revenus du mois
        $monthRevenue = StripeTransaction::where('created_at', '>=', now()->startOfMonth())
            ->whereIn('status', ['succeeded', 'paid'])
            ->sum('amount');
        $items[] = ['label' => 'Revenus du mois', 'value' => number_format($monthRevenue, 2, ',', ' ') . ' €', 'severity' => 'info'];

        if ($failRate > 10) {
            $recommendations[] = "Taux d'échec de paiement élevé ({$failRate}%) — vérifier les méthodes de paiement.";
        }
        if ($overdueEcheances > 5) {
            $recommendations[] = 'Beaucoup d\'échéances en retard — relancer les clients ou automatiser les relances.';
        }

        return $this->result($score, $items, $recommendations);
    }
}
