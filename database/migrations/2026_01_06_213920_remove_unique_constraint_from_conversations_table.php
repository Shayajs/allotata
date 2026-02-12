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
        // Supprimer la contrainte unique pour permettre plusieurs conversations
        // entre le même utilisateur et la même entreprise (une par contexte)
        
        // Désactiver temporairement la vérification des FK
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        
        // Utiliser une requête qui ignore les erreurs si l'index n'existe pas
        try {
            DB::statement('ALTER TABLE conversations DROP INDEX conversations_user_id_entreprise_id_unique');
        } catch (\Exception $e) {
            // Si l'index n'existe pas ou a un nom différent, essayer sans nom spécifique
            // ou simplement continuer
        }
        
        // Réactiver la vérification des FK
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        
        // Ajouter un index non-unique pour les performances
        Schema::table('conversations', function (Blueprint $table) {
            $table->index(['user_id', 'entreprise_id'], 'conversations_user_entreprise_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Supprimer l'index non-unique
            $table->dropIndex('conversations_user_entreprise_index');
            
            // Recréer la contrainte unique (cela peut échouer s'il y a des doublons)
            try {
                $table->unique(['user_id', 'entreprise_id']);
            } catch (\Exception $e) {
                // Ne pas échouer si on ne peut pas recréer l'unicité
            }
        });
    }
};
