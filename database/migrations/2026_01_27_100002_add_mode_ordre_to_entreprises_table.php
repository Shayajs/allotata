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
        Schema::table('entreprises', function (Blueprint $table) {
            // Mode d'ordre pour les services : 'manuel', 'ventes', 'statistiques'
            $table->string('mode_ordre_services')->default('manuel')->after('vente_sur_place_disponible_par_defaut');
            // Mode d'ordre pour les produits : 'manuel', 'ventes', 'statistiques'
            $table->string('mode_ordre_produits')->default('manuel')->after('mode_ordre_services');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->dropColumn(['mode_ordre_services', 'mode_ordre_produits']);
        });
    }
};
