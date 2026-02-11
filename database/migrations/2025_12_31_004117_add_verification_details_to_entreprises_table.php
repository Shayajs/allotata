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
            // Vérification du nom
            $table->boolean('nom_valide')->nullable()->after('est_verifiee'); // null = pas vérifié, true = validé, false = refusé
            $table->text('nom_refus_raison')->nullable()->after('nom_valide');
            
            // Vérification du SIREN (remplace siren_verifie mais on garde pour compatibilité)
            $table->boolean('siren_valide')->nullable()->after('siren_verifie'); // null = pas vérifié, true = validé, false = refusé
            $table->text('siren_refus_raison')->nullable()->after('siren_valide');
            
            // Raison du refus global
            $table->text('raison_refus_globale')->nullable()->after('siren_refus_raison');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->dropColumn([
                'nom_valide',
                'nom_refus_raison',
                'siren_valide',
                'siren_refus_raison',
                'raison_refus_globale',
            ]);
        });
    }
};
