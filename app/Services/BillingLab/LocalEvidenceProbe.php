<?php

namespace App\Services\BillingLab;

use App\Models\Echeance;
use App\Models\PlayPurchase;
use App\Models\ScheduledTaskLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LocalEvidenceProbe
{
    /**
     * Instantané lecture seule de la base courante (prod si on est dessus).
     *
     * @return array<string, mixed>
     */
    public function run(): array
    {
        return [
            'stripe_mode' => BillingLabGuard::mode(),
            'stripe_live_blocked' => BillingLabGuard::isLiveMode(),
            'jour_facturation' => $this->jourFacturation(),
            'dual_engine_this_month' => $this->dualEngineThisMonth(),
            'play_with_stripe_echeance' => $this->playWithStripeEcheance(),
            'scheduled_tasks' => $this->scheduledTasks(),
            'play_config' => [
                'service_account_present' => is_file((string) config('play.service_account_json')),
                'package' => config('play.package_name'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function jourFacturation(): array
    {
        if (! Schema::hasColumn('users', 'jour_facturation')) {
            return ['available' => false];
        }

        $rows = User::query()
            ->selectRaw('jour_facturation, COUNT(*) as c')
            ->groupBy('jour_facturation')
            ->pluck('c', 'jour_facturation');

        return [
            'available' => true,
            'null' => (int) ($rows[null] ?? $rows[''] ?? 0),
            'day_1' => (int) ($rows[1] ?? 0),
            'other' => (int) $rows->filter(fn ($count, $day) => $day !== null && $day !== '' && (int) $day !== 1)->sum(),
            'distribution' => $rows->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dualEngineThisMonth(): array
    {
        if (! Schema::hasTable('subscriptions') || ! Schema::hasTable('echeances')) {
            return ['available' => false];
        }

        $start = now()->copy()->startOfMonth()->toDateString();
        $end = now()->copy()->endOfMonth()->toDateString();

        $cashierIds = DB::table('subscriptions')
            ->where('type', 'default')
            ->where('stripe_status', 'active')
            ->pluck('user_id');

        $matches = Echeance::query()
            ->whereIn('user_id', $cashierIds)
            ->whereNull('entreprise_id')
            ->where('subscription_type', Echeance::TYPE_DEFAULT)
            ->where('statut', Echeance::STATUT_PAYE)
            ->whereDate('periode_debut', $start)
            ->whereDate('periode_fin', $end)
            ->get(['id', 'user_id', 'montant_final', 'stripe_payment_intent_id']);

        return [
            'available' => true,
            'count' => $matches->count(),
            'user_ids' => $matches->pluck('user_id')->unique()->values()->all(),
            'sample' => $matches->take(10)->toArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function playWithStripeEcheance(): array
    {
        if (! Schema::hasTable('play_purchases')) {
            return ['available' => false];
        }

        $playUserIds = PlayPurchase::query()->distinct()->pluck('user_id');
        $echeances = Echeance::query()
            ->whereIn('user_id', $playUserIds)
            ->where('payment_origin', Echeance::ORIGIN_AUTO_CARD)
            ->where('payment_provider', Echeance::PROVIDER_STRIPE)
            ->whereNotIn('statut', [Echeance::STATUT_ANNULE, Echeance::STATUT_ARRETE])
            ->get(['id', 'user_id', 'subscription_type', 'statut', 'periode_debut']);

        return [
            'available' => true,
            'count' => $echeances->count(),
            'user_ids' => $echeances->pluck('user_id')->unique()->values()->all(),
            'sample' => $echeances->take(10)->toArray(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function scheduledTasks(): array
    {
        if (! Schema::hasTable('scheduled_task_logs')) {
            return [];
        }

        $commands = [
            'subscriptions:check-echeances',
            'subscriptions:process-payments',
            'subscriptions:reconcile-echeances',
            'subscriptions:generate-invoices',
            'play:sync-purchases',
        ];

        return collect($commands)->map(function (string $command) {
            $last = ScheduledTaskLog::query()->where('command', $command)->latest('id')->first();

            return [
                'command' => $command,
                'last_status' => $last?->status,
                'last_finished_at' => $last?->finished_at?->toIso8601String(),
                'last_exit_code' => $last?->exit_code,
            ];
        })->all();
    }
}
