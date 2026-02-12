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
            // Options de livraison/vente par défaut pour l'entreprise
            $table->boolean('livraison_disponible_par_defaut')->default(true);
            $table->boolean('vente_sur_place_disponible_par_defaut')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->dropColumn(['livraison_disponible_par_defaut', 'vente_sur_place_disponible_par_defaut']);
        });
    }
};
