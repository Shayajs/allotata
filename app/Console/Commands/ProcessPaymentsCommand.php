<?php

namespace App\Console\Commands;

use App\Models\Echeance;
use App\Models\EntrepriseSubscription;
use App\Models\PaymentAuditLog;
use App\Services\PaymentVerificationService;
use App\Services\StripeCustomerService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;
use Stripe\Stripe;

/**
 * Auto-charge des échéances a_payer et retry des échecs.
 *
 * Logique :
 * 1. Prélève automatiquement les échéances « a_payer » si l'utilisateur a une carte enregistrée.
 * 2. Retente les échéances « echec » le lendemain, jusqu'à 3 tentatives max.
 * 3. Au bout de 7 jours ou 3 échecs : annule l'échéance ET l'abonnement lié.
 *
 * Première souscription (new) : paiement manuel sur /checkout → pas de retry CRON.
 * Renouvellement (CRON)       : dette réelle → retry ici.
 */
class ProcessPaymentsCommand extends Command
{
    protected $signature = 'subscriptions:process-payments
                            {--dry-run : Simule sans débiter}';

    protected $description = 'Auto-charge les échéances a_payer et retente les paiements échoués (3 tentatives, annulation après 7j)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('⚠ Mode dry-run : aucune charge Stripe ne sera effectuée.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $charged = 0;
        $retried = 0;
        $cancelled = 0;
        $skipped = 0;

        // ═══════════════════════════════════════════════════════
        // 1. Auto-charge des échéances « a_payer »
        // ═══════════════════════════════════════════════════════
        $aPayer = Echeance::where('statut', Echeance::STATUT_A_PAYER)
            ->with(['user', 'entreprise'])
            ->get();

        $this->info("Échéances à prélever : {$aPayer->count()}");

        foreach ($aPayer as $echeance) {
            $user = $echeance->user;
            if (!$user || empty($user->stripe_payment_method_id)) {
                $this->line("  #{$echeance->id} : pas de carte → ignoré.");
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line("  #{$echeance->id} : [dry-run] charge de {$echeance->montant_final} €.");
                $skipped++;
                continue;
            }

            $result = $this->attemptCharge($echeance, $user, 0);
            if ($result === 'ok') {
                $charged++;
            } elseif ($result === 'failed') {
                $retried++;
            } else {
                $skipped++;
            }
        }

        // ═══════════════════════════════════════════════════════
        // 2. Retry des échéances « echec »
        // ═══════════════════════════════════════════════════════
        $echecs = Echeance::where('statut', Echeance::STATUT_ECHEC)
            ->with(['user', 'entreprise'])
            ->get();

        $this->info("Échéances en échec à traiter : {$echecs->count()}");

        foreach ($echecs as $echeance) {
            $user = $echeance->user;
            $meta = $echeance->metadata ?? [];
            $retryCount = (int) ($meta['retry_count'] ?? 0);
            $createdAt = $echeance->created_at;

            // ── Règle : annuler après 3 tentatives ou 7 jours ──
            if ($retryCount >= 3 || ($createdAt && $createdAt->diffInDays(now()) >= 7)) {
                $this->warn("  #{$echeance->id} : retry={$retryCount}, âge={$createdAt?->diffInDays(now())}j → annulation.");

                if (!$dryRun) {
                    $echeance->update(['statut' => Echeance::STATUT_ANNULE]);
                    $this->cancelRelatedSubscription($echeance);

                    try {
                        PaymentAuditLog::log('auto_cancel_after_retries', $echeance->user_id, [
                            'echeance_id' => $echeance->id,
                            'retry_count' => $retryCount,
                            'days_since_creation' => $createdAt?->diffInDays(now()),
                            'message' => 'Échéance et abonnement annulés après 3 échecs / 7 jours.',
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning('ProcessPayments: audit log failed', ['error' => $e->getMessage()]);
                    }
                }

                $cancelled++;
                continue;
            }

            // ── Ne pas retenter le même jour (1 tentative / jour) ──
            $lastRetry = $meta['last_retry_at'] ?? null;
            if ($lastRetry && Carbon::parse($lastRetry)->isToday()) {
                $this->line("  #{$echeance->id} : déjà tenté aujourd'hui → ignoré.");
                $skipped++;
                continue;
            }

            if (!$user || empty($user->stripe_payment_method_id)) {
                $this->line("  #{$echeance->id} : pas de carte → ignoré.");
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line("  #{$echeance->id} : [dry-run] retry #{$retryCount} de {$echeance->montant_final} €.");
                $skipped++;
                continue;
            }

            $result = $this->attemptCharge($echeance, $user, $retryCount + 1);
            if ($result === 'ok') {
                $this->info("  #{$echeance->id} : retry #{$retryCount} → PAYÉ.");
                $charged++;
            } elseif ($result === 'failed') {
                $this->warn("  #{$echeance->id} : retry #{$retryCount} → ÉCHEC.");
                $retried++;
            } else {
                $skipped++;
            }
        }

        $this->info("═══ Résultat : chargés={$charged}, retentés/échoués={$retried}, annulés={$cancelled}, ignorés={$skipped} ═══");
        return self::SUCCESS;
    }

    /**
     * Tente de débiter une échéance via PaymentIntent off_session.
     *
     * @return string 'ok' | 'failed' | 'skip'
     */
    protected function attemptCharge(Echeance $echeance, $user, int $retryCount): string
    {
        $montant = (float) ($echeance->montant_final ?? $echeance->montant_du ?? 0);
        if ($montant <= 0) {
            return 'skip';
        }

        try {
            $customerId = StripeCustomerService::ensureCustomer($user);
        } catch (\Throwable $e) {
            Log::error('ProcessPayments: ensureCustomer failed', [
                'echeance_id' => $echeance->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return 'skip';
        }

        $amountCents = (int) round($montant * 100);

        try {
            $pi = PaymentIntent::create([
                'amount' => $amountCents,
                'currency' => \App\Models\Tarif::currency(),
                'customer' => $customerId,
                'payment_method' => $user->stripe_payment_method_id,
                'off_session' => true,
                'confirm' => true,
                'metadata' => [
                    'user_id' => (string) $user->id,
                    'echeance_id' => (string) $echeance->id,
                    'auto_charge' => 'true',
                    'retry_count' => (string) $retryCount,
                ],
            ]);
        } catch (\Stripe\Exception\CardException $e) {
            // Carte refusée → marquer en échec avec compteur de retry
            $echeance->update([
                'statut' => Echeance::STATUT_ECHEC,
                'metadata' => array_merge($echeance->metadata ?? [], [
                    'retry_count' => $retryCount,
                    'last_retry_at' => now()->toIso8601String(),
                    'last_error' => $e->getMessage(),
                    'last_error_code' => $e->getError()->code ?? null,
                ]),
            ]);

            try {
                PaymentAuditLog::log('auto_charge_fail', $echeance->user_id, [
                    'echeance_id' => $echeance->id,
                    'amount' => $montant,
                    'retry_count' => $retryCount,
                    'status' => 'card_error',
                    'context' => [
                        'code' => $e->getError()->code ?? null,
                        'decline_code' => $e->getError()->decline_code ?? null,
                    ],
                    'message' => "Auto-charge échec (retry #{$retryCount}) : " . $e->getMessage(),
                ]);
            } catch (\Throwable $ae) {
                Log::warning('ProcessPayments: audit log failed', ['error' => $ae->getMessage()]);
            }

            return 'failed';
        } catch (\Throwable $e) {
            Log::error('ProcessPayments: unexpected error during charge', [
                'echeance_id' => $echeance->id,
                'error' => $e->getMessage(),
            ]);
            return 'skip';
        }

        $status = $pi->status ?? '';

        if ($status === 'succeeded') {
            try {
                $result = PaymentVerificationService::markEcheancePaidFromPaymentIntent($pi->id);
                if ($result['ok']) {
                    try {
                        PaymentAuditLog::log('auto_charge_ok', $echeance->user_id, [
                            'echeance_id' => $echeance->id,
                            'stripe_payment_intent_id' => $pi->id,
                            'amount' => $montant,
                            'retry_count' => $retryCount,
                            'status' => 'succeeded',
                            'message' => 'Auto-charge réussi – ' . $echeance->libelle(),
                        ]);
                    } catch (\Throwable $ae) {
                        Log::warning('ProcessPayments: audit log failed', ['error' => $ae->getMessage()]);
                    }
                    return 'ok';
                }
            } catch (\Throwable $e) {
                Log::error('ProcessPayments: markPaid failed', [
                    'echeance_id' => $echeance->id,
                    'pi' => $pi->id,
                    'error' => $e->getMessage(),
                ]);
            }
            return 'skip';
        }

        if ($status === 'requires_action') {
            // 3DS requis en auto-charge → mettre en attente, l'utilisateur devra finaliser
            $echeance->update([
                'statut' => Echeance::STATUT_EN_ATTENTE,
                'stripe_payment_intent_id' => $pi->id,
                'metadata' => array_merge($echeance->metadata ?? [], [
                    'retry_count' => $retryCount,
                    'last_retry_at' => now()->toIso8601String(),
                    'requires_3ds' => true,
                ]),
            ]);

            // TODO : envoyer un email « Finalisez votre paiement (3DS) »
            Log::info('ProcessPayments: 3DS required', [
                'echeance_id' => $echeance->id,
                'payment_intent_id' => $pi->id,
            ]);

            return 'skip';
        }

        // Statut inattendu
        Log::warning('ProcessPayments: unexpected PI status', [
            'echeance_id' => $echeance->id,
            'pi' => $pi->id,
            'status' => $status,
        ]);
        return 'skip';
    }

    /**
     * Annule l'abonnement lié à une échéance (entreprise ou premium).
     */
    protected function cancelRelatedSubscription(Echeance $echeance): void
    {
        // ── Abonnement entreprise ──
        if ($echeance->entreprise_id && $echeance->subscription_type) {
            $sub = EntrepriseSubscription::where('entreprise_id', $echeance->entreprise_id)
                ->where('type', $echeance->subscription_type)
                ->first();

            if ($sub && $sub->estActif()) {
                $sub->update(['ends_at' => now()]);

                Log::info('ProcessPayments: enterprise subscription cancelled', [
                    'entreprise_id' => $echeance->entreprise_id,
                    'type' => $echeance->subscription_type,
                    'echeance_id' => $echeance->id,
                ]);
            }
            return;
        }

        // ── Abonnement Premium (Cashier) ──
        if ($echeance->subscription_type === Echeance::TYPE_DEFAULT) {
            $user = $echeance->user;
            if ($user && $user->subscribed('default')) {
                try {
                    $user->subscription('default')->cancel();
                    Log::info('ProcessPayments: premium subscription cancelled', [
                        'user_id' => $user->id,
                        'echeance_id' => $echeance->id,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('ProcessPayments: premium cancel failed', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}
