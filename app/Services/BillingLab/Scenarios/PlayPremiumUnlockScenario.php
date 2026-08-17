<?php

namespace App\Services\BillingLab\Scenarios;

use App\Services\BillingLab\ScenarioContext;
use App\Services\BillingLab\ScenarioResult;
use App\Services\PlayBilling\PlayBillingFulfillment;
use Carbon\Carbon;

class PlayPremiumUnlockScenario extends AbstractScenario
{
    public function id(): string
    {
        return 'play_premium_unlock';
    }

    public function label(): string
    {
        return 'Premium Play : fulfill → hasActivePlayPremium';
    }

    public function group(): string
    {
        return 'unlock';
    }

    public function run(ScenarioContext $ctx): ScenarioResult
    {
        if ($skip = $this->requirePlayTable()) {
            return $skip;
        }

        $ctx->useFakePlay();
        $user = $ctx->fixtures->user();
        $token = 'play-lab-premium-'.uniqid();
        $productId = (string) config('play.products.premium.id');

        $ctx->fakePlay->setResponse($token, [
            'valid' => true,
            'expires_at' => Carbon::now()->addMonth(),
            'acknowledged' => true,
        ]);

        $result = app(PlayBillingFulfillment::class)->fulfill($user, $productId, $token, 'GPA.LAB.1');
        $user->refresh();

        if (! $result['granted'] || ! $user->hasActivePlayPremium() || ! $user->aAbonnementActif()) {
            return ScenarioResult::fail('Fulfill Play n’a pas débloqué Premium.', [
                'granted' => $result['granted'],
                'play' => $user->hasActivePlayPremium(),
                'access' => $user->aAbonnementActif(),
            ]);
        }

        return ScenarioResult::pass('Premium débloqué via Google Play.', [
            'ledger' => $ctx->ledger->forUser($user),
        ]);
    }
}
