<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Forcer la suppression de la contrainte unique de toutes les manières possibles
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        
        // Méthode 1 : Supprimer par le nom exact de l'index
        try {
            DB::statement('ALTER TABLE conversations DROP INDEX conversations_user_id_entreprise_id_unique');
        } catch (\Exception $e) {
            // Continuer
        }
        
        // Méthode 2 : Chercher tous les index uniques sur ces colonnes et les supprimer
        $uniqueIndexes = DB::select("
            SELECT DISTINCT INDEX_NAME 
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'conversations' 
            AND NON_UNIQUE = 0
            AND (
                (COLUMN_NAME = 'user_id' AND SEQ_IN_INDEX = 1) OR
                (COLUMN_NAME = 'entreprise_id' AND SEQ_IN_INDEX = 2)
            )
        ");
        
        foreach ($uniqueIndexes as $idx) {
            try {
                DB::statement("ALTER TABLE conversations DROP INDEX `{$idx->INDEX_NAME}`");
            } catch (\Exception $e) {
                // Continuer
            }
        }
        
        // Méthode 3 : Supprimer toutes les contraintes uniques possibles
        $allUnique = DB::select("
            SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as columns
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'conversations' 
            AND NON_UNIQUE = 0
            AND COLUMN_NAME IN ('user_id', 'entreprise_id')
            GROUP BY INDEX_NAME
            HAVING columns = 'user_id,entreprise_id' OR columns = 'entreprise_id,user_id'
        ");
        
        foreach ($allUnique as $idx) {
            try {
                DB::statement("ALTER TABLE conversations DROP INDEX `{$idx->INDEX_NAME}`");
            } catch (\Exception $e) {
                // Continuer
            }
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ne rien faire en rollback car on ne veut pas recréer la contrainte
    }
};
