<?php

namespace App\Services\BillingLab\Scenarios;

use App\Services\BillingLab\BillingLabScenario;
use App\Services\BillingLab\ScenarioResult;
use Illuminate\Support\Facades\Schema;

abstract class AbstractScenario implements BillingLabScenario
{
    public function requiresStripeLive(): bool
    {
        return false;
    }

    protected function requirePlayTable(): ?ScenarioResult
    {
        if (Schema::hasTable('play_purchases')) {
            return null;
        }

        return ScenarioResult::skipped('Table play_purchases absente : migration Play non appliquée sur cette base.');
    }
}
