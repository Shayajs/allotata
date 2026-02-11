<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Vérifier si la table existe avant de la modifier
        // Cette migration peut s'exécuter avant la création de la table
        // Dans ce cas, on ne fait rien car la table sera créée avec les bonnes valeurs
        if (Schema::hasTable('entreprise_visites')) {
            // Pour MySQL, on doit modifier l'enum en recréant la colonne
            DB::statement("ALTER TABLE entreprise_visites MODIFY COLUMN page_type ENUM('accueil', 'agenda', 'store', 'services', 'produits') DEFAULT 'accueil'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Vérifier si la table existe avant de la modifier
        if (Schema::hasTable('entreprise_visites')) {
            // Revenir à l'ancien enum
            DB::statement("ALTER TABLE entreprise_visites MODIFY COLUMN page_type ENUM('accueil', 'agenda', 'store') DEFAULT 'accueil'");
        }
    }
};
