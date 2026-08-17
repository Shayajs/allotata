<?php

namespace App\Services\BillingLab;

use App\Models\Echeance;
use App\Models\PlayPurchase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Laravel\Cashier\Subscription;

class ChargeLedger
{
    /**
     * @return array{
     *     user_id:int,
     *     access:bool,
     *     cashier:list<array<string,mixed>>,
     *     echeances:list<array<string,mixed>>,
     *     play:list<array<string,mixed>>,
     *     fake_charges:int
     * }
     */
    public function forUser(User $user, ?FakeStripeProvider $fake = null): array
    {
        $user->refresh();

        return [
            'user_id' => $user->id,
            'access' => $user->aAbonnementActif(),
            'jour_facturation' => $user->jour_facturation,
            'premium_actif_jusqu' => $user->premium_actif_jusqu?->toDateString(),
            'payment_provider' => $user->payment_provider,
            'cashier' => $user->subscriptions()->get()->map(fn (Subscription $sub) => [
                'type' => $sub->type,
                'stripe_status' => $sub->stripe_status,
                'stripe_id' => $sub->stripe_id,
                'ends_at' => $sub->ends_at?->toIso8601String(),
                'valid' => $sub->valid(),
            ])->all(),
            'echeances' => Echeance::query()
                ->where('user_id', $user->id)
                ->orderBy('id')
                ->get()
                ->map(fn (Echeance $echeance) => [
                    'id' => $echeance->id,
                    'type' => $echeance->subscription_type,
                    'statut' => $echeance->statut,
                    'origine' => $echeance->payment_origin,
                    'montant' => $echeance->montant_final,
                    'periode' => [$echeance->periode_debut?->toDateString(), $echeance->periode_fin?->toDateString()],
                    'pi' => $echeance->stripe_payment_intent_id,
                ])
                ->all(),
            'play' => Schema::hasTable('play_purchases')
                ? PlayPurchase::query()
                    ->where('user_id', $user->id)
                    ->get()
                    ->map(fn (PlayPurchase $purchase) => [
                        'grants' => $purchase->grants,
                        'status' => $purchase->status,
                        'expires_at' => $purchase->expires_at?->toIso8601String(),
                        'active' => $purchase->isActive(),
                    ])
                    ->all()
                : [],
            'fake_charges' => $fake ? count($fake->charges) : 0,
        ];
    }

    /**
     * @return array{detected:bool,cashier_active:bool,echeance_payee:bool,fake_pi_count:int}
     */
    public function detectDoubleEngine(User $user, ?FakeStripeProvider $fake = null): array
    {
        $cashierActive = $user->subscriptions()
            ->where('type', 'default')
            ->where('stripe_status', 'active')
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->exists();

        $start = now()->copy()->startOfMonth();
        $end = now()->copy()->endOfMonth();

        $echeancePayee = Echeance::query()
            ->where('user_id', $user->id)
            ->whereNull('entreprise_id')
            ->where('subscription_type', Echeance::TYPE_DEFAULT)
            ->where('statut', Echeance::STATUT_PAYE)
            ->whereDate('periode_debut', $start)
            ->whereDate('periode_fin', $end)
            ->exists();

        $fakePi = $fake
            ? collect($fake->charges)->where('user_id', $user->id)->where('behavior', 'ok')->count()
            : 0;

        return [
            'detected' => $cashierActive && ($echeancePayee || $fakePi > 0),
            'cashier_active' => $cashierActive,
            'echeance_payee' => $echeancePayee,
            'fake_pi_count' => $fakePi,
        ];
    }

    public function periodBounds(?Carbon $at = null): array
    {
        $at = $at?->copy() ?? now();

        return [$at->copy()->startOfMonth(), $at->copy()->endOfMonth()];
    }
}
