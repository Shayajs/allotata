<?php

namespace App\Services\BillingLab\Scenarios;

use App\Services\BillingLab\ScenarioContext;
use App\Services\BillingLab\ScenarioResult;
use App\Services\PlayBilling\PlayBillingFulfillment;
use Carbon\Carbon;

class PlayRenewalThenExpireScenario extends AbstractScenario
{
    public function id(): string
    {
        return 'play_renewal_then_expire';
    }

    public function label(): string
    {
        return 'Play : renouvellement puis expiration';
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
        $token = 'play-lab-renew-'.uniqid();
        $productId = (string) config('play.products.premium.id');
        $fulfillment = app(PlayBillingFulfillment::class);

        $ctx->fakePlay->setResponse($token, [
            'valid' => true,
            'expires_at' => Carbon::now()->addDays(30),
            'acknowledged' => true,
            'payload' => ['subscriptionState' => 'SUBSCRIPTION_STATE_ACTIVE'],
        ]);
        $result = $fulfillment->fulfill($user, $productId, $token);
        $user->refresh();

        if (! $user->hasActivePlayPremium()) {
            return ScenarioResult::fail('Play actif attendu après fulfill.');
        }

        $ctx->fakePlay->setResponse($token, [
            'valid' => true,
            'expires_at' => Carbon::now()->addDays(60),
            'acknowledged' => true,
            'payload' => ['subscriptionState' => 'SUBSCRIPTION_STATE_ACTIVE'],
        ]);
        $fulfillment->refresh($result['purchase']->fresh());
        $user->refresh();

        if (! $user->hasActivePlayPremium()) {
            return ScenarioResult::fail('Play aurait dû rester actif après refresh +30j.');
        }

        $ctx->fakePlay->setResponse($token, [
            'valid' => false,
            'expires_at' => Carbon::now()->subDay(),
            'acknowledged' => true,
            'payload' => ['subscriptionState' => 'SUBSCRIPTION_STATE_EXPIRED'],
        ]);
        $fulfillment->refresh($result['purchase']->fresh());
        $user->refresh();

        if ($user->hasActivePlayPremium() || $user->aAbonnementActif()) {
            return ScenarioResult::fail('Play expiré devrait couper l’accès.', [
                'play' => $user->hasActivePlayPremium(),
                'access' => $user->aAbonnementActif(),
            ]);
        }

        return ScenarioResult::pass('Play : ACTIVE débloque, EXPIRED coupe l’accès.');
    }
}
