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
        // Ajouter les champs de renouvellement pour les abonnements manuels utilisateurs
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'abonnement_manuel_type_renouvellement')) {
                $table->enum('abonnement_manuel_type_renouvellement', ['mensuel', 'annuel'])->nullable()->after('abonnement_manuel_notes');
            }
            if (!Schema::hasColumn('users', 'abonnement_manuel_jour_renouvellement')) {
                $table->integer('abonnement_manuel_jour_renouvellement')->nullable()->after('abonnement_manuel_type_renouvellement'); // Jour du mois (1-31)
            }
            if (!Schema::hasColumn('users', 'abonnement_manuel_date_debut')) {
                $table->date('abonnement_manuel_date_debut')->nullable()->after('abonnement_manuel_jour_renouvellement'); // Date de début de l'abonnement
            }
            if (!Schema::hasColumn('users', 'abonnement_manuel_montant')) {
                $table->decimal('abonnement_manuel_montant', 10, 2)->nullable()->after('abonnement_manuel_date_debut'); // Montant de l'abonnement
            }
        });

        // Ajouter les champs de renouvellement pour les abonnements manuels entreprises
        Schema::table('entreprise_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('entreprise_subscriptions', 'type_renouvellement')) {
                $table->enum('type_renouvellement', ['mensuel', 'annuel'])->nullable()->after('notes_manuel');
            }
            if (!Schema::hasColumn('entreprise_subscriptions', 'jour_renouvellement')) {
                $table->integer('jour_renouvellement')->nullable()->after('type_renouvellement'); // Jour du mois (1-31)
            }
            if (!Schema::hasColumn('entreprise_subscriptions', 'date_debut')) {
                $table->date('date_debut')->nullable()->after('jour_renouvellement'); // Date de début de l'abonnement
            }
            if (!Schema::hasColumn('entreprise_subscriptions', 'montant')) {
                $table->decimal('montant', 10, 2)->nullable()->after('date_debut'); // Montant de l'abonnement
            }
        });

        // Modifier la table factures pour permettre de lier une facture à un abonnement manuel
        Schema::table('factures', function (Blueprint $table) {
            if (!Schema::hasColumn('factures', 'entreprise_subscription_id')) {
                $table->foreignId('entreprise_subscription_id')->nullable()->after('user_id')->constrained('entreprise_subscriptions')->onDelete('cascade');
            }
            if (!Schema::hasColumn('factures', 'type_facture')) {
                $table->enum('type_facture', ['reservation', 'abonnement_manuel'])->default('reservation')->after('entreprise_subscription_id');
            }
            // Note: Pour les abonnements manuels utilisateurs, on utilise déjà user_id
            // Note: reservation_id peut être null pour les factures d'abonnement
            // Note: entreprise_id peut être null pour les factures d'abonnement utilisateur
        });

        // Modifier reservation_id pour permettre null (si nécessaire)
        // On utilise DB::statement car Schema::table ne permet pas de modifier les contraintes facilement
        try {
            \DB::statement('ALTER TABLE factures MODIFY reservation_id BIGINT UNSIGNED NULL');
        } catch (\Exception $e) {
            // Ignorer si déjà nullable ou si erreur
            \Log::info('Migration factures: reservation_id déjà nullable ou erreur: ' . $e->getMessage());
        }
        
        // Modifier entreprise_id pour permettre null (nécessaire pour les factures d'abonnement utilisateur)
        try {
            // Récupérer le nom de la contrainte foreign key
            $constraintName = \DB::selectOne("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'factures' 
                AND COLUMN_NAME = 'entreprise_id' 
                AND CONSTRAINT_NAME != 'PRIMARY'
                LIMIT 1
            ");
            
            if ($constraintName) {
                // Supprimer la contrainte
                \DB::statement("ALTER TABLE factures DROP FOREIGN KEY {$constraintName->CONSTRAINT_NAME}");
                // Modifier la colonne
                \DB::statement('ALTER TABLE factures MODIFY entreprise_id BIGINT UNSIGNED NULL');
                // Recréer la contrainte
                \DB::statement("ALTER TABLE factures ADD CONSTRAINT {$constraintName->CONSTRAINT_NAME} FOREIGN KEY (entreprise_id) REFERENCES entreprises(id) ON DELETE CASCADE");
            } else {
                // Pas de contrainte, modifier directement
                \DB::statement('ALTER TABLE factures MODIFY entreprise_id BIGINT UNSIGNED NULL');
            }
        } catch (\Exception $e) {
            // Ignorer si déjà nullable ou si erreur
            \Log::info('Migration factures: entreprise_id déjà nullable ou erreur: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'abonnement_manuel_type_renouvellement')) {
                $table->dropColumn('abonnement_manuel_type_renouvellement');
            }
            if (Schema::hasColumn('users', 'abonnement_manuel_jour_renouvellement')) {
                $table->dropColumn('abonnement_manuel_jour_renouvellement');
            }
            if (Schema::hasColumn('users', 'abonnement_manuel_date_debut')) {
                $table->dropColumn('abonnement_manuel_date_debut');
            }
            if (Schema::hasColumn('users', 'abonnement_manuel_montant')) {
                $table->dropColumn('abonnement_manuel_montant');
            }
        });

        Schema::table('entreprise_subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('entreprise_subscriptions', 'type_renouvellement')) {
                $table->dropColumn('type_renouvellement');
            }
            if (Schema::hasColumn('entreprise_subscriptions', 'jour_renouvellement')) {
                $table->dropColumn('jour_renouvellement');
            }
            if (Schema::hasColumn('entreprise_subscriptions', 'date_debut')) {
                $table->dropColumn('date_debut');
            }
            if (Schema::hasColumn('entreprise_subscriptions', 'montant')) {
                $table->dropColumn('montant');
            }
        });

        Schema::table('factures', function (Blueprint $table) {
            if (Schema::hasColumn('factures', 'entreprise_subscription_id')) {
                $table->dropForeign(['entreprise_subscription_id']);
                $table->dropColumn('entreprise_subscription_id');
            }
            if (Schema::hasColumn('factures', 'type_facture')) {
                $table->dropColumn('type_facture');
            }
        });
    }
};
