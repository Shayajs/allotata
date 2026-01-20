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
        Schema::table('horaires_ouverture', function (Blueprint $table) {
            // Type d'exception : 'jour', 'mois', 'plage', 'jours_feries'
            $table->string('type_exception', 20)->nullable()->after('est_exceptionnel');
            
            // Pour les plages et mois
            $table->date('date_debut')->nullable()->after('type_exception');
            $table->date('date_fin')->nullable()->after('date_debut');
            
            // Pour les mois
            $table->integer('mois')->nullable()->after('date_fin')->comment('1-12');
            $table->integer('annee')->nullable()->after('mois');
            
            // Jours de la semaine à exclure (JSON array)
            $table->json('jours_exclus')->nullable()->after('annee');
            
            // Pour les jours fériés
            $table->boolean('est_jours_feries')->default(false)->after('jours_exclus');
            $table->integer('annee_jours_feries')->nullable()->after('est_jours_feries');
            $table->string('zone_jours_feries', 50)->nullable()->after('annee_jours_feries')->default('metropole');
            
            // Index pour améliorer les performances
            $table->index(['entreprise_id', 'type_exception']);
            $table->index(['date_debut', 'date_fin']);
            $table->index(['mois', 'annee']);
        });
        
        // Mettre à jour les enregistrements existants pour qu'ils soient de type 'jour'
        DB::table('horaires_ouverture')
            ->where('est_exceptionnel', true)
            ->whereNull('type_exception')
            ->update(['type_exception' => 'jour']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('horaires_ouverture', function (Blueprint $table) {
            $table->dropIndex(['entreprise_id', 'type_exception']);
            $table->dropIndex(['date_debut', 'date_fin']);
            $table->dropIndex(['mois', 'annee']);
            
            $table->dropColumn([
                'type_exception',
                'date_debut',
                'date_fin',
                'mois',
                'annee',
                'jours_exclus',
                'est_jours_feries',
                'annee_jours_feries',
                'zone_jours_feries',
            ]);
        });
    }
};
