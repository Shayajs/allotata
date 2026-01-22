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
            // Options de livraison/vente spécifiques au produit
            // Si null, utilise les paramètres par défaut de l'entreprise
            $table->boolean('livraison_disponible')->nullable()->after('gestion_stock');
            $table->boolean('vente_sur_place_disponible')->nullable()->after('livraison_disponible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->dropColumn(['livraison_disponible', 'vente_sur_place_disponible']);
        });
    }
};
