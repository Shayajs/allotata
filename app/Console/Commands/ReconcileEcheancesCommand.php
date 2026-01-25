<?php

namespace App\Console\Commands;

use App\Models\Echeance;
use App\Services\PaymentVerificationService;
use Illuminate\Console\Command;

/**
 * Réconciliation des échéances "en_attente" avec Stripe.
 *
 * 3e niveau de vérification : si le webhook a échoué et que l'utilisateur
 * n'a pas repassé par success, on interroge Stripe directement et on met
 * à jour le statut (payé → paye, sinon → a_payer pour retry).
 *
 * À lancer : au moment du paiement (déjà couvert par success) ou une fois
 * par mois / quotidiennement via CRON.
 */
class ReconcileEcheancesCommand extends Command
{
    protected $signature = 'subscriptions:reconcile-echeances';

    protected $description = 'Réconcilie les échéances en_attente avec Stripe (vérification directe API)';

    public function handle(): int
    {
        $this->info('Réconciliation des échéances en attente...');

        // 1. Échéances avec Checkout Session (flux legacy)
        $echeancesSession = Echeance::where('statut', Echeance::STATUT_EN_ATTENTE)
            ->whereNotNull('stripe_checkout_session_id')
            ->whereNull('stripe_payment_intent_id')
            ->get();

        // 2. Échéances avec PaymentIntent uniquement (flux moderne off_session)
        $echeancesPaymentIntent = Echeance::where('statut', Echeance::STATUT_EN_ATTENTE)
            ->whereNotNull('stripe_payment_intent_id')
            ->get();

        $echeances = $echeancesSession->merge($echeancesPaymentIntent);

        if ($echeances->isEmpty()) {
            $this->info('Aucune échéance en attente à réconcilier.');
            return self::SUCCESS;
        }

        $marked = 0;
        $reset = 0;

        foreach ($echeances as $echeance) {
            // Si c'est une Checkout Session, utiliser la méthode existante
            if ($echeance->stripe_checkout_session_id && !$echeance->stripe_payment_intent_id) {
                $sessionId = $echeance->stripe_checkout_session_id;
                try {
                    $result = PaymentVerificationService::verifyAndMarkPaid($sessionId);
                } catch (\Throwable $e) {
                    $this->error("  Échéance #{$echeance->id} : erreur Stripe ({$e->getMessage()}). Réinitialisation pour retry.");
                    $echeance->update([
                        'statut' => Echeance::STATUT_A_PAYER,
                        'stripe_checkout_session_id' => null,
                        'stripe_payment_intent_id' => null,
                    ]);
                    $reset++;
                    continue;
                }

                if ($result['ok']) {
                    if ($result['already']) {
                        $this->line("  Échéance #{$echeance->id} : déjà payée.");
                    } else {
                        $this->info("  Échéance #{$echeance->id} : marquée payée.");
                    }
                    $marked++;
                    continue;
                }

                $this->warn("  Échéance #{$echeance->id} : paiement non confirmé ({$result['message']}). Réinitialisation pour retry.");
                $echeance->update([
                    'statut' => Echeance::STATUT_A_PAYER,
                    'stripe_checkout_session_id' => null,
                    'stripe_payment_intent_id' => null,
                ]);
                $reset++;
                continue;
            }

            // Si c'est un PaymentIntent, vérifier directement
            if ($echeance->stripe_payment_intent_id) {
                $piId = $echeance->stripe_payment_intent_id;
                try {
                    $result = PaymentVerificationService::markEcheancePaidFromPaymentIntent($piId);
                } catch (\Throwable $e) {
                    $this->error("  Échéance #{$echeance->id} : erreur Stripe PaymentIntent ({$e->getMessage()}). Réinitialisation pour retry.");
                    $echeance->update([
                        'statut' => Echeance::STATUT_A_PAYER,
                        'stripe_payment_intent_id' => null,
                    ]);
                    $reset++;
                    continue;
                }

                if ($result['ok']) {
                    if ($result['already']) {
                        $this->line("  Échéance #{$echeance->id} : déjà payée (PaymentIntent).");
                    } else {
                        $this->info("  Échéance #{$echeance->id} : marquée payée (PaymentIntent).");
                    }
                    $marked++;
                    continue;
                }

                $this->warn("  Échéance #{$echeance->id} : PaymentIntent non confirmé ({$result['message']}). Réinitialisation pour retry.");
                $echeance->update([
                    'statut' => Echeance::STATUT_A_PAYER,
                    'stripe_payment_intent_id' => null,
                ]);
                $reset++;
            }
        }

        $this->info("Réconciliation terminée : {$marked} payée(s), {$reset} réinitialisée(s).");
        return self::SUCCESS;
    }
}
