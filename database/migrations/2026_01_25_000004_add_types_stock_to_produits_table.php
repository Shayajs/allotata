<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            // Modifier le champ gestion_stock pour supporter plus de types
            // Les valeurs existantes restent valides : 'disponible_immediatement', 'en_attente_commandes'
            // Nouveaux types possibles :
            // - 'disponible_immediatement' : Stock géré, décrément automatique
            // - 'en_attente_commandes' : Pas de stock, commande puis achat
            // - 'precommande' : Produit disponible en précommande (date de disponibilité)
            // - 'sur_mesure' : Produit fait sur mesure, délai de fabrication
            // - 'location' : Produit en location (gestion de la durée)
            // - 'abonnement' : Produit avec abonnement récurrent
            // Le champ reste un enum pour l'instant, mais on peut l'étendre
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pas de rollback nécessaire car on ne modifie pas la structure
    }
};
