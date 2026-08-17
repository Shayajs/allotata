<?php

namespace App\Console\Commands;

use App\Services\PremiumAccessService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Subscription;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;

class MigratePremiumToEcheancesCommand extends Command
{
    protected $signature = 'subscriptions:migrate-premium-to-echeances
                            {--dry-run : Simule sans écrire ni appeler Stripe}
                            {--user-id= : Limiter à un ou plusieurs user_id (virgules)}';

    protected $description = 'Bascule les abonnés Cashier Premium vers les échéances (cancel_at_period_end, zéro double débit)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->warn('Mode dry-run : aucune écriture ni appel Stripe mutatif.');
        }

        $query = Subscription::query()
            ->where('type', 'default')
            ->whereIn('stripe_status', ['active', 'trialing'])
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->with('user');

        $scoped = $this->scopedUserIds();
        if ($scoped !== []) {
            $query->whereIn('user_id', $scoped);
        }

        $migrated = 0;
        $skipped = 0;
        $anomalies = [];

        foreach ($query->get() as $subscription) {
            $user = $subscription->user;
            if (! $user) {
                $anomalies[] = [
                    'subscription_id' => $subscription->id,
                    'reason' => 'user_missing',
                ];
                $skipped++;
                continue;
            }

            $periodEnd = $this->resolvePeriodEnd($subscription, $dryRun);
            if (! $periodEnd) {
                $anomalies[] = [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'stripe_id' => $subscription->stripe_id,
                    'reason' => 'period_end_missing',
                ];
                $skipped++;
                continue;
            }

            $this->line(sprintf(
                '  user #%d : period_end=%s jour_facturation=%d stripe_id=%s',
                $user->id,
                $periodEnd->toDateString(),
                $user->jour_facturation ?: (int) $periodEnd->copy()->addDay()->day,
                $subscription->stripe_id
            ));

            if (! $dryRun) {
                $this->scheduleStripeCancelAtPeriodEnd($subscription);
                PremiumAccessService::applyLocalCashierMigration($user, $subscription, $periodEnd);
            }

            $migrated++;
        }

        $this->info("Migrés : {$migrated}, ignorés : {$skipped}, anomalies : ".count($anomalies));
        if ($anomalies !== []) {
            $this->warn(json_encode($anomalies, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        return self::SUCCESS;
    }

    private function resolvePeriodEnd(Subscription $subscription, bool $dryRun): ?Carbon
    {
        if ($subscription->ends_at) {
            return $subscription->ends_at->copy();
        }

        if ($dryRun) {
            return now()->addMonth();
        }

        if (! $subscription->stripe_id || ! str_starts_with((string) $subscription->stripe_id, 'sub_')) {
            return now()->addMonth();
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $stripeSub = StripeSubscription::retrieve($subscription->stripe_id);
            $timestamp = $stripeSub->current_period_end ?? $stripeSub->cancel_at ?? null;
            if ($timestamp) {
                return Carbon::createFromTimestamp((int) $timestamp);
            }
        } catch (\Throwable $e) {
            Log::warning('migrate-premium-to-echeances: lecture Stripe impossible', [
                'subscription_id' => $subscription->id,
                'stripe_id' => $subscription->stripe_id,
                'error' => $e->getMessage(),
            ]);
        }

        return now()->addMonth();
    }

    private function scheduleStripeCancelAtPeriodEnd(Subscription $subscription): void
    {
        if (! $subscription->stripe_id || ! str_starts_with((string) $subscription->stripe_id, 'sub_')) {
            return;
        }

        if (str_starts_with((string) $subscription->stripe_id, 'sub_lab_')) {
            return;
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            StripeSubscription::update($subscription->stripe_id, [
                'cancel_at_period_end' => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning('migrate-premium-to-echeances: cancel_at_period_end échoué', [
                'subscription_id' => $subscription->id,
                'stripe_id' => $subscription->stripe_id,
                'error' => $e->getMessage(),
            ]);
            $this->warn("  Stripe cancel_at_period_end échoué pour {$subscription->stripe_id} : {$e->getMessage()}");
        }
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
