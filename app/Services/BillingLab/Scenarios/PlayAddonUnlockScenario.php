<?php

namespace App\Services\BillingLab\Scenarios;

use App\Services\BillingLab\ScenarioContext;
use App\Services\BillingLab\ScenarioResult;
use App\Services\PlayBilling\PlayBillingFulfillment;
use Carbon\Carbon;

class PlayAddonUnlockScenario extends AbstractScenario
{
    public function id(): string
    {
        return 'play_addon_unlock';
    }

    public function label(): string
    {
        return 'Add-on Play : site_web → aSiteWebActif';
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
        $entreprise = $ctx->fixtures->entreprise($user);
        $token = 'play-lab-site-'.uniqid();
        $productId = (string) config('play.products.site_web.id');

        $ctx->fakePlay->setResponse($token, [
            'valid' => true,
            'expires_at' => Carbon::now()->addMonth(),
            'acknowledged' => true,
        ]);

        app(PlayBillingFulfillment::class)->fulfill($user, $productId, $token, 'GPA.LAB.SITE', $entreprise->id);
        $entreprise->refresh();

        if (! $entreprise->aSiteWebActif()) {
            return ScenarioResult::fail('Add-on Play site_web n’a pas débloqué l’option.', [
                'sub' => $entreprise->abonnementSiteWeb()?->toArray(),
            ]);
        }

        return ScenarioResult::pass('Site web débloqué via Play (actif_jusqu).', [
            'actif_jusqu' => $entreprise->abonnementSiteWeb()?->actif_jusqu?->toDateString(),
        ]);
    }
}
