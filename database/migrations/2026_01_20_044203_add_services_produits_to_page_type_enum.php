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
        // Pour MySQL, on doit modifier l'enum en recréant la colonne
        DB::statement("ALTER TABLE entreprise_visites MODIFY COLUMN page_type ENUM('accueil', 'agenda', 'store', 'services', 'produits') DEFAULT 'accueil'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revenir à l'ancien enum
        DB::statement("ALTER TABLE entreprise_visites MODIFY COLUMN page_type ENUM('accueil', 'agenda', 'store') DEFAULT 'accueil'");
    }
};
