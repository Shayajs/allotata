<?php

namespace Database\Factories;

use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Entreprise>
 */
class EntrepriseFactory extends Factory
{
    protected $model = Entreprise::class;

    public function definition(): array
    {
        $nom = fake()->company();

        return [
            'user_id' => User::factory(),
            'nom' => $nom,
            'slug' => Str::slug($nom).'-'.fake()->unique()->numerify('###'),
            'type_activite' => 'Coiffeuse',
            'email' => fake()->unique()->companyEmail(),
            'telephone' => '0601020304',
            'status_juridique' => 'auto_entrepreneur',
            'siret' => '73282932000074',
            'siren' => '732829320',
            'adresse_rue' => '1 rue de la Paix',
            'code_postal' => '75001',
            'ville' => 'Paris',
            'assujetti_tva' => false,
            'taux_tva_defaut' => 20,
            'pdf_couleur_primaire' => '#059669',
            'pdf_couleur_secondaire' => '#1F2937',
        ];
    }

    public function incomplet(): static
    {
        return $this->state(fn () => [
            'siret' => null,
            'siren' => null,
            'adresse_rue' => null,
            'code_postal' => null,
            'status_juridique' => 'en_cours',
        ]);
    }

    public function assujettiTva(): static
    {
        return $this->state(fn () => [
            'assujetti_tva' => true,
            'tva_intracommunautaire' => 'FR32732829320',
            'taux_tva_defaut' => 20,
        ]);
    }
}
