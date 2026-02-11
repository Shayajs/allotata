<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ForumCategory;

class ForumCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Catégories admin (nouveautés)
        ForumCategory::create([
            'nom' => 'Nouveautés',
            'slug' => 'nouveautes',
            'description' => 'Les dernières nouveautés et annonces importantes',
            'ordre' => 1,
            'admin_only' => true,
        ]);

        // Catégories publiques
        ForumCategory::create([
            'nom' => 'Demandes',
            'slug' => 'demandes',
            'description' => 'Partagez vos demandes et besoins',
            'ordre' => 2,
            'admin_only' => false,
        ]);

        ForumCategory::create([
            'nom' => 'Discussions générales',
            'slug' => 'discussions-generales',
            'description' => 'Discussions diverses sur Allotata',
            'ordre' => 3,
            'admin_only' => false,
        ]);

        ForumCategory::create([
            'nom' => 'Aide & Support',
            'slug' => 'aide-support',
            'description' => 'Posez vos questions et obtenez de l\'aide',
            'ordre' => 4,
            'admin_only' => false,
        ]);
    }
}
