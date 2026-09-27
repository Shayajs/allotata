<?php

namespace App\Console\Commands;

use App\Services\ManualSubscriptionService;
use Illuminate\Console\Command;

class SyncManualSubscriptionPeriodsCommand extends Command
{
    protected $signature = 'subscriptions:sync-manual-periods';

    protected $description = 'Ouvre les échéances manuelles arrivées à date et envoie les rappels. N\'annule jamais un abonnement.';

    public function handle(ManualSubscriptionService $subscriptions): int
    {
        $created = $subscriptions->syncAllActive(true);
        $pending = $subscriptions->countMembersNeedingReview();

        $this->info("Échéances manuelles créées : {$created}. Membres à vérifier : {$pending}.");

        return self::SUCCESS;
    }
}