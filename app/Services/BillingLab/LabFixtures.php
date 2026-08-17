<?php

namespace App\Services\BillingLab;

use App\Models\Echeance;
use App\Models\Entreprise;
use App\Models\EntrepriseSubscription;
use App\Models\PlayPurchase;
use App\Models\Tarif;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;

class LabFixtures
{
    public const EMAIL_DOMAIN = 'allotata-lab.test';

    public const EMAIL_PREFIX = 'billing-lab-';

    /** @var list<int> */
    public array $createdUserIds = [];

    public function ensureTarifs(): void
    {
        foreach ([
            'default' => ['amount' => 14, 'label' => 'Abonnement Premium'],
            'site_web' => ['amount' => 2, 'label' => 'Site Web Vitrine'],
            'multi_personnes' => ['amount' => 20, 'label' => 'Gestion Multi-Personnes'],
        ] as $type => $data) {
            Tarif::query()->updateOrCreate(
                ['type' => $type],
                ['amount' => $data['amount'], 'currency' => 'eur', 'label' => $data['label']]
            );
        }

        Tarif::clearCache();
    }

    public function user(array $attributes = []): User
    {
        $this->ensureTarifs();

        $suffix = Str::lower(Str::random(8));

        $user = User::factory()->create(array_merge([
            'name' => 'Lab',
            'surname' => 'Billing',
            'email' => self::EMAIL_PREFIX.$suffix.'@'.self::EMAIL_DOMAIN,
            'password' => Hash::make('password'),
            'est_gerant' => true,
            'email_verified_at' => now(),
        ], $attributes));

        $this->createdUserIds[] = $user->id;

        return $user;
    }

    public function entreprise(User $user, array $attributes = []): Entreprise
    {
        $nom = $attributes['nom'] ?? 'Lab Entreprise '.Str::upper(Str::random(4));

        return Entreprise::factory()->create(array_merge([
            'user_id' => $user->id,
            'nom' => $nom,
            'slug' => Str::slug($nom).'-'.Str::lower(Str::random(6)),
        ], $attributes));
    }

    public function cashierSubscription(User $user, array $attributes = []): Subscription
    {
        return $user->subscriptions()->create(array_merge([
            'type' => 'default',
            'name' => 'default',
            'stripe_id' => 'sub_lab_'.Str::lower(Str::random(14)),
            'stripe_status' => 'active',
            'stripe_price' => 'price_lab',
            'quantity' => 1,
        ], $attributes));
    }

    public function echeance(User $user, array $attributes = []): Echeance
    {
        return Echeance::factory()->create(array_merge([
            'user_id' => $user->id,
            'metadata' => ['is_billing_lab' => true],
        ], $attributes));
    }

    public function stripeEcheancePremium(User $user, array $attributes = []): User
    {
        $debut = $attributes['periode_debut'] ?? now()->copy()->startOfDay();
        $fin = $attributes['periode_fin'] ?? now()->copy()->addMonth()->subDay()->startOfDay();

        $user->forceFill([
            'payment_provider' => Echeance::PROVIDER_STRIPE,
            'jour_facturation' => $attributes['jour_facturation'] ?? (int) $debut->day,
            'premium_actif_jusqu' => $attributes['premium_actif_jusqu'] ?? $fin,
            'stripe_payment_method_id' => $attributes['stripe_payment_method_id'] ?? 'pm_lab_visa',
        ])->save();

        return $user->fresh();
    }

    /**
     * @return array{users:int,entreprises:int,echeances:int,play:int,subscriptions:int}
     */
    public function cleanup(): array
    {
        $users = User::query()
            ->where('email', 'like', self::EMAIL_PREFIX.'%@'.self::EMAIL_DOMAIN)
            ->get();

        $userIds = $users->pluck('id');
        $entrepriseIds = Entreprise::query()->whereIn('user_id', $userIds)->pluck('id');

        $echeances = Echeance::query()
            ->where(function ($q) use ($userIds) {
                $q->whereIn('user_id', $userIds)
                    ->orWhereJsonContains('metadata->is_billing_lab', true);
            })
            ->count();

        Echeance::query()
            ->where(function ($q) use ($userIds) {
                $q->whereIn('user_id', $userIds)
                    ->orWhereJsonContains('metadata->is_billing_lab', true);
            })
            ->delete();

        $play = 0;
        if (Schema::hasTable('play_purchases')) {
            $play = PlayPurchase::query()->whereIn('user_id', $userIds)->count();
            PlayPurchase::query()->whereIn('user_id', $userIds)->delete();
        }

        EntrepriseSubscription::query()->whereIn('entreprise_id', $entrepriseIds)->delete();

        $subscriptions = Subscription::query()->whereIn('user_id', $userIds)->count();
        Subscription::query()->whereIn('user_id', $userIds)->delete();

        Entreprise::query()->whereIn('id', $entrepriseIds)->delete();
        $deletedUsers = $users->count();
        User::query()->whereIn('id', $userIds)->delete();

        return [
            'users' => $deletedUsers,
            'entreprises' => $entrepriseIds->count(),
            'echeances' => $echeances,
            'play' => $play,
            'subscriptions' => $subscriptions,
        ];
    }
}
