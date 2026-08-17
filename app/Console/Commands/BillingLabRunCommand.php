<?php

namespace App\Console\Commands;

use App\Services\BillingLab\BillingLabGuard;
use App\Services\BillingLab\LabFixtures;
use App\Services\BillingLab\ScenarioRunner;
use Illuminate\Console\Command;

class BillingLabRunCommand extends Command
{
    protected $signature = 'billing-lab:run
                            {scenario? : Identifiant d’un scénario}
                            {--all : Lancer toute la matrice}
                            {--live : Inclure les vrais appels Stripe test (sk_test_ uniquement)}
                            {--json : Sortie JSON}
                            {--cleanup : Nettoyer les fixtures billing-lab}';

    protected $description = 'Laboratoire de facturation : rejoue un mois de paiements sans attendre';

    public function handle(ScenarioRunner $runner, LabFixtures $fixtures): int
    {
        if ($this->option('cleanup')) {
            $cleaned = $fixtures->cleanup();
            $this->info('Nettoyage : '.json_encode($cleaned, JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $allowLive = (bool) $this->option('live');

        if ($allowLive) {
            try {
                BillingLabGuard::assertNotLive();
            } catch (\RuntimeException $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }
        }
        $scenario = $this->argument('scenario');

        if ($this->option('all') || ! $scenario) {
            $report = $runner->runAll($allowLive);
            $this->renderReport($report);

            return ($report['summary']['fail'] ?? 0) > 0 ? self::FAILURE : self::SUCCESS;
        }

        $result = $runner->run($scenario, $allowLive);
        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->line(($result['status'] ?? '?').' '.$result['id'].' — '.$result['message']);
        }

        return ($result['ok'] ?? false) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function renderReport(array $report): void
    {
        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return;
        }

        $this->info('Mode : '.($report['mode'] ?? '?'));
        $this->table(
            ['id', 'statut', 'message'],
            collect($report['results'] ?? [])->map(fn (array $row) => [
                $row['id'] ?? '',
                $row['status'] ?? '',
                \Illuminate\Support\Str::limit((string) ($row['message'] ?? ''), 90),
            ])->all()
        );

        $summary = $report['summary'] ?? [];
        $this->info('Résumé : '.json_encode($summary, JSON_UNESCAPED_UNICODE));

        $dual = data_get($report, 'evidence.dual_engine_this_month.count');
        $this->line('Preuve base courante — Cashier + échéance payée ce mois : '.($dual ?? 'n/a'));
    }
}
