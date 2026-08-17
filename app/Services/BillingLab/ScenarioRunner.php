<?php

namespace App\Services\BillingLab;

use App\Services\BillingLab\Scenarios\AnniversaryJourFacturationScenario;
use App\Services\BillingLab\Scenarios\CardDeclineNoUnlockScenario;
use App\Services\BillingLab\Scenarios\CashierAccessAfterMonthScenario;
use App\Services\BillingLab\Scenarios\CashierMigrationNoDoubleChargeScenario;
use App\Services\BillingLab\Scenarios\DoubleChargeEvidenceScenario;
use App\Services\BillingLab\Scenarios\EntrepriseOptionUnlockScenario;
use App\Services\BillingLab\Scenarios\JourFacturationUnsetScenario;
use App\Services\BillingLab\Scenarios\MonthEndClampScenario;
use App\Services\BillingLab\Scenarios\PlayAddonUnlockScenario;
use App\Services\BillingLab\Scenarios\PlayExpiryRevokeScenario;
use App\Services\BillingLab\Scenarios\PlayPremiumUnlockScenario;
use App\Services\BillingLab\Scenarios\PlayRenewalThenExpireScenario;
use App\Services\BillingLab\Scenarios\PlayStripeIsolationScenario;
use App\Services\BillingLab\Scenarios\PremiumAnniversaryScenario;
use App\Services\BillingLab\Scenarios\PremiumGraceThenRevokeScenario;
use App\Services\BillingLab\Scenarios\PremiumSingleChargerScenario;
use App\Services\BillingLab\Scenarios\RetryCancelAfterFailuresScenario;
use App\Services\BillingLab\Scenarios\StripeLivePiChargeScenario;
use App\Services\BillingLab\Scenarios\StripePremiumUnlockScenario;
use App\Services\BillingLab\Scenarios\StripeTestClockRenewalScenario;
use App\Services\BillingLab\Scenarios\ThreeDsPendingThenUnlockScenario;
use App\Services\BillingLab\Scenarios\TripleNetIdempotenceScenario;
use App\Services\BillingLab\Scenarios\WalletSameAsStripeScenario;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ScenarioRunner
{
    public const CACHE_KEY = 'billing_lab.last_report';

    /**
     * @return list<BillingLabScenario>
     */
    public function catalog(): array
    {
        return [
            new StripePremiumUnlockScenario,
            new PremiumSingleChargerScenario,
            new EntrepriseOptionUnlockScenario,
            new PlayPremiumUnlockScenario,
            new PlayAddonUnlockScenario,
            new WalletSameAsStripeScenario,
            new ThreeDsPendingThenUnlockScenario,
            new CardDeclineNoUnlockScenario,
            new AnniversaryJourFacturationScenario,
            new PremiumAnniversaryScenario,
            new MonthEndClampScenario,
            new JourFacturationUnsetScenario,
            new CashierAccessAfterMonthScenario,
            new PlayRenewalThenExpireScenario,
            new PlayExpiryRevokeScenario,
            new RetryCancelAfterFailuresScenario,
            new PremiumGraceThenRevokeScenario,
            new DoubleChargeEvidenceScenario,
            new CashierMigrationNoDoubleChargeScenario,
            new PlayStripeIsolationScenario,
            new TripleNetIdempotenceScenario,
            new StripeLivePiChargeScenario,
            new StripeTestClockRenewalScenario,
        ];
    }

    public function find(string $id): ?BillingLabScenario
    {
        foreach ($this->catalog() as $scenario) {
            if ($scenario->id() === $id) {
                return $scenario;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function run(string $id, bool $allowStripeLive = false): array
    {
        $scenario = $this->find($id);
        if (! $scenario) {
            return [
                'id' => $id,
                'ok' => false,
                'status' => 'fail',
                'message' => "Scénario inconnu : {$id}",
            ];
        }

        return $this->execute($scenario, $allowStripeLive);
    }

    /**
     * @return array{results: list<array<string,mixed>>, summary: array<string,int>, evidence: array<string,mixed>}
     */
    public function runAll(bool $allowStripeLive = false): array
    {
        $results = [];
        foreach ($this->catalog() as $scenario) {
            if ($scenario->requiresStripeLive() && ! $allowStripeLive) {
                $results[] = [
                    'id' => $scenario->id(),
                    'label' => $scenario->label(),
                    'group' => $scenario->group(),
                    'ok' => true,
                    'status' => 'skipped',
                    'message' => 'Scénario Stripe test ignoré (passer --live / allow_live).',
                    'details' => [],
                    'findings' => [],
                    'elapsed_ms' => 0,
                ];
                continue;
            }

            $results[] = $this->execute($scenario, $allowStripeLive);
        }

        $report = [
            'results' => $results,
            'summary' => $this->summarize($results),
            'evidence' => app(LocalEvidenceProbe::class)->run(),
            'mode' => BillingLabGuard::mode(),
            'ran_at' => now()->toIso8601String(),
        ];

        Cache::put(self::CACHE_KEY, $report, now()->addDay());

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    private function execute(BillingLabScenario $scenario, bool $allowStripeLive): array
    {
        if ($scenario->requiresStripeLive() || $allowStripeLive) {
            BillingLabGuard::assertNotLive();
        }

        $clock = new LaravelClock;
        $fixtures = new LabFixtures;
        $ctx = new ScenarioContext(
            $fixtures,
            $clock,
            new ChargeLedger,
            new FakeStripeProvider,
            new FakePlayBillingVerifier,
            $allowStripeLive && BillingLabGuard::canCallStripe(),
        );

        $start = microtime(true);

        try {
            if (! $scenario->requiresStripeLive()) {
                $ctx->useFakeStripe();
                $ctx->useFakePlay();
            }

            $result = $scenario->run($ctx);
            $payload = $result->toArray();
        } catch (Throwable $e) {
            $payload = ScenarioResult::fail($e->getMessage(), [
                'exception' => $e::class,
                'file' => basename($e->getFile()).':'.$e->getLine(),
            ])->toArray();
        } finally {
            $ctx->restoreProviders();
            $clock->reset();
            $fixtures->cleanup();
        }

        return array_merge([
            'id' => $scenario->id(),
            'label' => $scenario->label(),
            'group' => $scenario->group(),
            'elapsed_ms' => (int) round((microtime(true) - $start) * 1000),
        ], $payload);
    }

    /**
     * @param  list<array<string, mixed>>  $results
     * @return array<string, int>
     */
    private function summarize(array $results): array
    {
        $summary = [
            'pass' => 0,
            'fail' => 0,
            'evidence_risk' => 0,
            'evidence_safe' => 0,
            'skipped' => 0,
        ];

        foreach ($results as $result) {
            $status = $result['status'] ?? 'fail';
            if (! isset($summary[$status])) {
                $summary[$status] = 0;
            }
            $summary[$status]++;
        }

        return $summary;
    }
}
