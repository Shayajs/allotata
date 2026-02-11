<?php
/**
 * Script pour exécuter les migrations manuellement
 * À exécuter UNE SEULE FOIS via le navigateur : http://votre-domaine.com/run_migrations.php
 * SUPPRIMEZ CE FICHIER après utilisation pour des raisons de sécurité !
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Exécution des migrations</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>Exécution des migrations</h1>
    
    <?php
    try {
        // Vérifier si la colonne existe déjà
        $hasColumn = Schema::hasColumn('users', 'notifications_erreurs_actives');
        $hasTable = Schema::hasTable('error_logs');
        
        if ($hasColumn && $hasTable) {
            echo '<div class="success">✓ Les migrations ont déjà été exécutées. Tout est à jour !</div>';
        } else {
            echo '<div class="info">Exécution des migrations en cours...</div>';
            
            // Ajouter la colonne notifications_erreurs_actives
            if (!$hasColumn) {
                try {
                    DB::statement('ALTER TABLE `users` ADD COLUMN `notifications_erreurs_actives` BOOLEAN DEFAULT FALSE AFTER `is_admin`');
                    echo '<div class="success">✓ Colonne <code>notifications_erreurs_actives</code> ajoutée à la table <code>users</code></div>';
                } catch (\Exception $e) {
                    echo '<div class="error">✗ Erreur lors de l\'ajout de la colonne : ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            } else {
                echo '<div class="info">→ La colonne <code>notifications_erreurs_actives</code> existe déjà</div>';
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
                    echo '<div class="success">✓ Table <code>error_logs</code> créée</div>';
                } catch (\Exception $e) {
                    echo '<div class="error">✗ Erreur lors de la création de la table : ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            } else {
                echo '<div class="info">→ La table <code>error_logs</code> existe déjà</div>';
            }
            
            // Vérification finale
            $hasColumn = Schema::hasColumn('users', 'notifications_erreurs_actives');
            $hasTable = Schema::hasTable('error_logs');
            
            if ($hasColumn && $hasTable) {
                echo '<div class="success"><strong>✓ Migrations terminées avec succès !</strong></div>';
                echo '<div class="info">Vous pouvez maintenant supprimer ce fichier <code>run_migrations.php</code> pour des raisons de sécurité.</div>';
            }
        }
        
    } catch (\Exception $e) {
        echo '<div class="error"><strong>Erreur :</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
        echo '<div class="error">Stack trace : <pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre></div>';
    }
    ?>
    
    <hr>
    <p><small>Ce script doit être supprimé après utilisation pour des raisons de sécurité.</small></p>
</body>
</html>
