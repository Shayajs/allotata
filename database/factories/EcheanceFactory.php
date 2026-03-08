<?php

namespace Database\Factories;

use App\Models\Echeance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Echeance>
 */
class EcheanceFactory extends Factory
{
    protected $model = Echeance::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'entreprise_id' => null,
            'subscription_type' => Echeance::TYPE_DEFAULT,
            'payment_origin' => Echeance::ORIGIN_AUTO_CARD,
            'payment_provider' => Echeance::PROVIDER_STRIPE,
            'auto_charge_eligible' => true,
            'periode_debut' => now()->startOfMonth()->toDateString(),
            'periode_fin' => now()->endOfMonth()->toDateString(),
            'jour_facturation' => (int) now()->day,
            'montant_du' => 15.00,
            'montant_final' => 15.00,
            'reduction_promo' => 0,
            'reduction_manuel' => 0,
            'statut' => Echeance::STATUT_A_PAYER,
            'metadata' => ['factory' => true],
        ];
    }
}
