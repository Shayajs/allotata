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
        Schema::table('proposition_rendez_vouses', function (Blueprint $table) {
            // Ajouter la nouvelle colonne auteur_user_id si elle n'existe pas
            if (!Schema::hasColumn('proposition_rendez_vouses', 'auteur_user_id')) {
                $table->foreignId('auteur_user_id')->nullable()->after('message_id');
            }
            
            // Ajouter le type d'auteur (client ou gérant) si elle n'existe pas
            if (!Schema::hasColumn('proposition_rendez_vouses', 'auteur_type')) {
                $table->enum('auteur_type', ['client', 'gerant'])->nullable()->after('auteur_user_id');
            }
        });
        
        // Migrer les données existantes seulement si auteur_user_id est null
        // user_id devient auteur_user_id et on détermine auteur_type
        if (Schema::hasColumn('proposition_rendez_vouses', 'user_id')) {
            DB::statement("
                UPDATE proposition_rendez_vouses pr
                INNER JOIN conversations c ON pr.conversation_id = c.id
                INNER JOIN entreprises e ON pr.entreprise_id = e.id
                SET 
                    pr.auteur_user_id = COALESCE(pr.auteur_user_id, pr.user_id),
                    pr.auteur_type = COALESCE(pr.auteur_type, CASE 
                        WHEN pr.user_id = c.user_id THEN 'client'
                        WHEN pr.user_id = e.user_id THEN 'gerant'
                        ELSE 'client'
                    END)
                WHERE pr.auteur_user_id IS NULL OR pr.auteur_type IS NULL
            ");
        }
        
        Schema::table('proposition_rendez_vouses', function (Blueprint $table) {
            // Rendre auteur_user_id obligatoire maintenant que les données sont migrées
            if (Schema::hasColumn('proposition_rendez_vouses', 'auteur_user_id')) {
                // Vérifier si la contrainte de clé étrangère existe déjà
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'proposition_rendez_vouses' 
                    AND COLUMN_NAME = 'auteur_user_id' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                if (empty($foreignKeys)) {
                    $table->foreign('auteur_user_id')->references('id')->on('users')->onDelete('cascade');
                }
                
                // Rendre la colonne non nullable si elle est encore nullable
                $column = DB::select("
                    SELECT IS_NULLABLE 
                    FROM information_schema.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'proposition_rendez_vouses' 
                    AND COLUMN_NAME = 'auteur_user_id'
                ");
                
                if (!empty($column) && $column[0]->IS_NULLABLE === 'YES') {
                    $table->foreignId('auteur_user_id')->nullable(false)->change();
                }
            }
            
            // Ajouter un index pour améliorer les performances (nom court pour MySQL)
            // Vérifier si l'index existe déjà
            $indexes = DB::select("
                SELECT INDEX_NAME 
                FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'proposition_rendez_vouses' 
                AND INDEX_NAME = 'prop_conv_auteur_statut_idx'
            ");
            
            if (empty($indexes)) {
                $table->index(['conversation_id', 'auteur_type', 'statut'], 'prop_conv_auteur_statut_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposition_rendez_vouses', function (Blueprint $table) {
            // Supprimer l'index s'il existe
            $indexes = DB::select("
                SELECT INDEX_NAME 
                FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'proposition_rendez_vouses' 
                AND INDEX_NAME = 'prop_conv_auteur_statut_idx'
            ");
            
            if (!empty($indexes)) {
                $table->dropIndex('prop_conv_auteur_statut_idx');
            }
            
            // Supprimer la contrainte de clé étrangère si elle existe
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'proposition_rendez_vouses' 
                AND COLUMN_NAME = 'auteur_user_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            if (!empty($foreignKeys)) {
                $table->dropForeign([$foreignKeys[0]->CONSTRAINT_NAME]);
            }
            
            // Supprimer les colonnes si elles existent
            if (Schema::hasColumn('proposition_rendez_vouses', 'auteur_type')) {
                $table->dropColumn('auteur_type');
            }
            
            if (Schema::hasColumn('proposition_rendez_vouses', 'auteur_user_id')) {
                $table->dropColumn('auteur_user_id');
            }
        });
    }
};
