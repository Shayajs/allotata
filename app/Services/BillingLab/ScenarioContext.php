<?php

namespace App\Services\BillingLab;

use App\Console\Commands\CheckEcheancesCommand;
use App\Console\Commands\ProcessPaymentsCommand;
use App\Console\Commands\ReconcileEcheancesCommand;
use App\Console\Commands\SyncPlayPurchasesCommand;
use App\Services\Payments\ProviderResolver;
use App\Services\PlayBilling\PlayBillingFulfillment;
use App\Services\PlayBilling\PlayBillingVerifierContract;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ScenarioContext
{
    public function __construct(
        public LabFixtures $fixtures,
        public LaravelClock $clock,
        public ChargeLedger $ledger,
        public FakeStripeProvider $fakeStripe,
        public FakePlayBillingVerifier $fakePlay,
        public bool $allowStripeLive = false,
    ) {}

    public function useFakeStripe(): void
    {
        app()->forgetInstance(ProcessPaymentsCommand::class);
        app()->forgetInstance(ReconcileEcheancesCommand::class);
        app()->instance(ProviderResolver::class, new ProviderResolver([$this->fakeStripe]));
    }

    public function useFakePlay(): void
    {
        app()->instance(PlayBillingVerifierContract::class, $this->fakePlay);
        app()->forgetInstance(PlayBillingFulfillment::class);
    }

    /**
     * @return array<string, string>
     */
    private function userScope(): array
    {
        $ids = $this->fixtures->createdUserIds;
        if ($ids === []) {
            throw new \RuntimeException('Refus de lancer un CRON labo sans user-id : risque de toucher la prod.');
        }

        return ['--user-id' => implode(',', $ids)];
    }

    public function restoreProviders(): void
    {
        app()->forgetInstance(ProviderResolver::class);
        app()->forgetInstance(PlayBillingVerifierContract::class);
        app()->forgetInstance(PlayBillingFulfillment::class);
        app()->forgetInstance(ProcessPaymentsCommand::class);
        app()->forgetInstance(CheckEcheancesCommand::class);
        app()->forgetInstance(ReconcileEcheancesCommand::class);
        app()->forgetInstance(SyncPlayPurchasesCommand::class);
    }

    public function runCheckEcheances(): string
    {
        return $this->runFreshCommand(CheckEcheancesCommand::class, $this->userScope());
    }

    public function runProcessPayments(bool $dryRun = false): string
    {
        $params = $this->userScope();
        if ($dryRun) {
            $params['--dry-run'] = true;
        }

        return $this->runFreshCommand(ProcessPaymentsCommand::class, $params);
    }

    public function runReconcile(): string
    {
        return $this->runFreshCommand(ReconcileEcheancesCommand::class, []);
    }

    public function runPlaySync(): string
    {
        return $this->runFreshCommand(SyncPlayPurchasesCommand::class, $this->userScope());
    }

    /**
     * Instancie la commande via le container (fake providers) plutôt que
     * l’instance déjà résolue par `artisan billing-lab:run`.
     *
     * @param  array<string, mixed>  $params
     */
    private function runFreshCommand(string $commandClass, array $params): string
    {
        app()->forgetInstance($commandClass);
        $command = app()->make($commandClass);
        $command->setLaravel(app());
        $output = new BufferedOutput;
        $command->run(new ArrayInput($params), $output);

        return trim($output->fetch());
    }
}
