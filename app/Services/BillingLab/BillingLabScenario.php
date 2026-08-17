<?php

namespace App\Services\BillingLab;

interface BillingLabScenario
{
    public function id(): string;

    public function label(): string;

    public function group(): string;

    public function requiresStripeLive(): bool;

    public function run(ScenarioContext $ctx): ScenarioResult;
}
