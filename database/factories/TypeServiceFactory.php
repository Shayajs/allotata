<?php

namespace Database\Factories;

use App\Models\Entreprise;
use App\Models\TypeService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TypeService>
 */
class TypeServiceFactory extends Factory
{
    protected $model = TypeService::class;

    public function definition(): array
    {
        return [
            'entreprise_id' => Entreprise::factory(),
            'nom' => 'Coupe femme',
            'description' => 'Prestation de coiffure',
            'duree_minutes' => 60,
            'prix' => 50,
            'est_actif' => true,
            'type_structure' => 'ponctuel',
        ];
    }
}
