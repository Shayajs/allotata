<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RunErrorNotificationsMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:error-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exécute les migrations pour le système de notifications d\'erreurs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Exécution des migrations pour les notifications d\'erreurs...');
        
        try {
            // Vérifier si la colonne existe déjà
            $hasColumn = Schema::hasColumn('users', 'notifications_erreurs_actives');
            $hasTable = Schema::hasTable('error_logs');
            
            if ($hasColumn && $hasTable) {
                $this->info('✓ Les migrations ont déjà été exécutées. Tout est à jour !');
                return 0;
            }
            
            // Ajouter la colonne notifications_erreurs_actives
            if (!$hasColumn) {
                try {
                    DB::statement('ALTER TABLE `users` ADD COLUMN `notifications_erreurs_actives` BOOLEAN DEFAULT FALSE AFTER `is_admin`');
                    $this->info('✓ Colonne notifications_erreurs_actives ajoutée à la table users');
                } catch (\Exception $e) {
                    $this->error('✗ Erreur lors de l\'ajout de la colonne : ' . $e->getMessage());
                    return 1;
                }
            } else {
                $this->info('→ La colonne notifications_erreurs_actives existe déjà');
            }
            
            // Créer la table error_logs
            if (!$hasTable) {
                try {
                    DB::statement("
                        CREATE TABLE IF NOT EXISTS `error_logs` (
                          `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                          `level` VARCHAR(255) NOT NULL,
                          `message` VARCHAR(255) NOT NULL,
                          `context` TEXT NULL,
                          `file` VARCHAR(255) NULL,
                          `line` INT NULL,
                          `trace` TEXT NULL,
                          `url` VARCHAR(255) NULL,
                          `method` VARCHAR(255) NULL,
                          `ip` VARCHAR(255) NULL,
                          `user_agent` VARCHAR(255) NULL,
                          `user_id` BIGINT UNSIGNED NULL,
                          `est_vue` BOOLEAN DEFAULT FALSE,
                          `vu_at` TIMESTAMP NULL,
                          `created_at` TIMESTAMP NULL,
                          `updated_at` TIMESTAMP NULL,
                          PRIMARY KEY (`id`),
                          INDEX `idx_level` (`level`),
                          INDEX `idx_est_vue` (`est_vue`),
                          INDEX `idx_created_at` (`created_at`),
                          CONSTRAINT `error_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    $this->info('✓ Table error_logs créée');
                } catch (\Exception $e) {
                    $this->error('✗ Erreur lors de la création de la table : ' . $e->getMessage());
                    return 1;
                }
            } else {
                $this->info('→ La table error_logs existe déjà');
            }
            
            $this->info('');
            $this->info('✓ Migrations terminées avec succès !');
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Erreur : ' . $e->getMessage());
            $this->error('Stack trace : ' . $e->getTraceAsString());
            return 1;
        }
    }
}
