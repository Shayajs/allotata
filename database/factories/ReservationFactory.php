<?php

namespace Database\Factories;

use App\Models\Entreprise;
use App\Models\Reservation;
use App\Models\TypeService;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        $debut = now()->subDay()->setTime(10, 0);

        return [
            'user_id' => User::factory(),
            'entreprise_id' => Entreprise::factory(),
            'date_reservation' => $debut,
            'date_fin' => $debut->copy()->addHour(),
            'prix' => 50,
            'est_paye' => false,
            'statut' => 'confirmee',
            'type_service' => 'Coupe',
            'duree_minutes' => 60,
        ];
    }

    public function terminee(): static
    {
        return $this->state(fn () => ['statut' => 'terminee']);
    }
}
