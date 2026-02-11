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
        // Forcer la suppression de toutes les contraintes uniques sur user_id et entreprise_id
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        
        // Lister tous les index uniques possibles et les supprimer
        $indexes = DB::select("
            SELECT DISTINCT INDEX_NAME 
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'conversations' 
            AND NON_UNIQUE = 0
            AND (COLUMN_NAME = 'user_id' OR COLUMN_NAME = 'entreprise_id')
        ");
        
        foreach ($indexes as $index) {
            try {
                DB::statement("ALTER TABLE conversations DROP INDEX `{$index->INDEX_NAME}`");
            } catch (\Exception $e) {
                // Continuer même si l'index n'existe pas
            }
        }
        
        // Essayer aussi avec le nom exact de l'erreur
        try {
            DB::statement('ALTER TABLE conversations DROP INDEX IF EXISTS conversations_user_id_entreprise_id_unique');
        } catch (\Exception $e) {
            // Ignorer si ça échoue
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        
        // S'assurer qu'un index non-unique existe pour les performances
        Schema::table('conversations', function (Blueprint $table) {
            // Vérifier si l'index existe déjà
            $hasIndex = DB::select("
                SELECT COUNT(*) as count 
                FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'conversations' 
                AND INDEX_NAME = 'conversations_user_entreprise_index'
            ");
            
            if (empty($hasIndex) || $hasIndex[0]->count == 0) {
                $table->index(['user_id', 'entreprise_id'], 'conversations_user_entreprise_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conversations_user_entreprise_index');
            try {
                $table->unique(['user_id', 'entreprise_id']);
            } catch (\Exception $e) {
                // Ignorer si on ne peut pas recréer
            }
        });
    }
};
