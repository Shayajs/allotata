<?php

namespace App\Services\BillingLab\Scenarios;

use App\Services\BillingLab\ScenarioContext;
use App\Services\BillingLab\ScenarioResult;
use App\Services\PlayBilling\PlayBillingFulfillment;
use Carbon\Carbon;

class PlayExpiryRevokeScenario extends AbstractScenario
{
    public function id(): string
    {
        return 'play_expiry_revoke';
    }

    public function label(): string
    {
        return 'Play : expiration + play:sync-purchases coupe l’accès';
    }

    public function group(): string
    {
        return 'renewal';
    }

    public function run(ScenarioContext $ctx): ScenarioResult
    {
        if ($skip = $this->requirePlayTable()) {
            return $skip;
        }

        $ctx->useFakePlay();
        $user = $ctx->fixtures->user();
        $token = 'play-lab-expire-sync-'.uniqid();
        $productId = (string) config('play.products.premium.id');

        $ctx->fakePlay->setResponse($token, [
            'valid' => true,
            'expires_at' => Carbon::now()->addDays(30),
            'acknowledged' => true,
            'payload' => ['subscriptionState' => 'SUBSCRIPTION_STATE_ACTIVE'],
        ]);
        app(PlayBillingFulfillment::class)->fulfill($user, $productId, $token);
        $user->refresh();

        if (! $user->aAbonnementActif()) {
            return ScenarioResult::fail('Play actif n’a pas débloqué l’accès.');
        }

        $ctx->fakePlay->setResponse($token, [
            'valid' => false,
            'expires_at' => Carbon::now()->subDay(),
            'acknowledged' => true,
            'payload' => ['subscriptionState' => 'SUBSCRIPTION_STATE_EXPIRED'],
        ]);
        $ctx->runPlaySync();
        $user->refresh();

        $details = [
            'play' => $user->hasActivePlayPremium(),
            'access' => $user->aAbonnementActif(),
            'payment_provider' => $user->payment_provider,
            'ledger' => $ctx->ledger->forUser($user),
        ];

        if ($user->hasActivePlayPremium() || $user->aAbonnementActif()) {
            return ScenarioResult::fail('play:sync-purchases n’a pas coupé l’accès Play expiré.', $details);
        }

        return ScenarioResult::pass('Play expiré + sync CRON : accès coupé.', $details);
    }
}
