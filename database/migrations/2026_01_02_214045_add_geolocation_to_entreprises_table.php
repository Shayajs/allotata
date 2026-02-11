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
            // Adresse détaillée
            $table->string('adresse_rue')->nullable()->after('ville');
            $table->string('code_postal', 10)->nullable()->after('adresse_rue');
            
            // Coordonnées GPS pour la recherche par proximité
            $table->decimal('latitude', 10, 8)->nullable()->after('code_postal');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            
            // Option d'affichage : true = adresse complète, false = ville seulement
            $table->boolean('afficher_adresse_complete')->default(false)->after('longitude');
            
            // Index pour optimiser les recherches géographiques
            $table->index(['latitude', 'longitude']);
            $table->index('code_postal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropIndex(['code_postal']);
            $table->dropColumn([
                'adresse_rue',
                'code_postal',
                'latitude',
                'longitude',
                'afficher_adresse_complete',
            ]);
        });
    }
};
