<?php

namespace App\Console\Commands;

use App\Models\PlayPurchase;
use App\Services\PlayBilling\PlayBillingFulfillment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SyncPlayPurchasesCommand extends Command
{
    protected $signature = 'play:sync-purchases
                            {--user-id= : Limiter à un ou plusieurs user_id (virgules)}';

    protected $description = 'Rafraîchit les achats Google Play actifs (expiration, renouvellement)';

    public function handle(PlayBillingFulfillment $fulfillment): int
    {
        if (! Schema::hasTable('play_purchases')) {
            $this->warn('Table play_purchases absente.');

            return self::SUCCESS;
        }

        $query = PlayPurchase::query()->where('status', 'active');
        $scoped = $this->scopedUserIds();
        if ($scoped !== []) {
            $query->whereIn('user_id', $scoped);
        }

        $refreshed = 0;
        $expired = 0;

        foreach ($query->get() as $purchase) {
            $updated = $fulfillment->refresh($purchase);
            $refreshed++;
            if ($updated->status !== 'active' || ! $updated->isActive()) {
                $expired++;
            }
        }

        $this->info("Play sync : rafraîchis={$refreshed}, expirés={$expired}.");

        return self::SUCCESS;
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
