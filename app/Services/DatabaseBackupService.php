<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class DatabaseBackupService
{
    protected $backupPath;
    protected $connection;

    public function __construct()
    {
        $this->backupPath = storage_path('app/backups/database');
        $this->connection = config('database.default');
        
        // Créer le dossier de sauvegarde s'il n'existe pas
        if (!file_exists($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }

    /**
     * Créer une sauvegarde complète de la base de données
     */
    public function createBackup($description = null)
    {
        try {
            $config = config("database.connections.{$this->connection}");
            
            if (!in_array($config['driver'], ['mysql', 'mariadb'])) {
                throw new Exception("Le driver de base de données {$config['driver']} n'est pas supporté. Seuls MySQL et MariaDB sont supportés.");
            }

            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "backup_{$timestamp}.sql";
            $filepath = $this->backupPath . '/' . $filename;

            // Construire la commande mysqldump
            // Options importantes :
            // --single-transaction : Pour une sauvegarde cohérente sans verrous
            // --routines : Inclure les procédures stockées et fonctions
            // --triggers : Inclure les triggers
            // --add-drop-table : Supprimer les tables avant de les recréer (pour restauration propre)
            // --complete-insert : INSERT complets avec noms de colonnes (plus sûr pour restauration)
            // --extended-insert : INSERT optimisés (plus rapide, mais moins lisible)
            // --lock-tables=false : Pas de verrous (déjà géré par --single-transaction)
            // Par défaut, mysqldump inclut TOUTES les données (structure + données + relations)
            $passwordArg = !empty($config['password']) ? '--password=' . escapeshellarg($config['password']) : '';
            $command = sprintf(
                'mysqldump --host=%s --port=%s --user=%s %s --single-transaction --routines --triggers --add-drop-table --complete-insert --extended-insert --lock-tables=false %s > %s 2>&1',
                escapeshellarg($config['host']),
                escapeshellarg($config['port']),
                escapeshellarg($config['username']),
                $passwordArg,
                escapeshellarg($config['database']),
                escapeshellarg($filepath)
            );

            // Exécuter la commande
            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($filepath)) {
                $error = implode("\n", $output);
                throw new Exception("Erreur lors de la création de la sauvegarde: {$error}");
            }

            // Vérifier que le fichier n'est pas vide
            if (filesize($filepath) === 0) {
                unlink($filepath);
                throw new Exception("La sauvegarde est vide. Vérifiez les permissions et les identifiants de la base de données.");
            }

            // Vérifier que le fichier contient bien des données (INSERT statements)
            $fileContent = file_get_contents($filepath, false, null, 0, 10000); // Lire les 10 premiers KB
            if (strpos($fileContent, 'INSERT INTO') === false && strpos($fileContent, 'INSERT') === false) {
                // Le fichier pourrait ne contenir que la structure, vérifier plus en profondeur
                $fullContent = file_get_contents($filepath);
                if (strpos($fullContent, 'INSERT') === false && strpos($fullContent, 'VALUES') === false) {
                    Log::warning("La sauvegarde semble ne contenir que la structure, pas de données détectées");
                    // Ne pas échouer, car certaines bases peuvent être vides
                }
            }

            // Créer un fichier de métadonnées
            $metadata = [
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath),
                'created_at' => Carbon::now()->toDateTimeString(),
                'description' => $description,
                'database' => $config['database'],
                'driver' => $config['driver'],
            ];

            $metadataPath = $this->backupPath . '/' . str_replace('.sql', '.json', $filename);
            file_put_contents($metadataPath, json_encode($metadata, JSON_PRETTY_PRINT));

            // Enregistrer dans la base de données si la table existe
            try {
                if (DB::getSchemaBuilder()->hasTable('database_backups')) {
                    DB::table('database_backups')->insert([
                        'filename' => $filename,
                        'filepath' => $filepath,
                        'size' => filesize($filepath),
                        'description' => $description,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }
            } catch (Exception $e) {
                // Ignorer si la table n'existe pas encore
                Log::warning("Impossible d'enregistrer la sauvegarde dans la base de données: " . $e->getMessage());
            }

            Log::info("Sauvegarde créée avec succès: {$filename}");

            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath),
                'metadata' => $metadata,
            ];
        } catch (Exception $e) {
            Log::error("Erreur lors de la création de la sauvegarde: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Restaurer une sauvegarde depuis un fichier
     */
    public function restoreBackup($filepath, $confirm = false)
    {
        if (!$confirm) {
            throw new Exception("La restauration nécessite une confirmation explicite.");
        }

        try {
            if (!file_exists($filepath)) {
                throw new Exception("Le fichier de sauvegarde n'existe pas: {$filepath}");
            }

            $config = config("database.connections.{$this->connection}");
            
            if (!in_array($config['driver'], ['mysql', 'mariadb'])) {
                throw new Exception("Le driver de base de données {$config['driver']} n'est pas supporté.");
            }

            // Désactiver les contraintes de clés étrangères temporairement
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Construire la commande mysql pour restaurer
            // Utiliser --password= pour éviter l'invite interactive si le mot de passe est vide
            $passwordArg = !empty($config['password']) ? '--password=' . escapeshellarg($config['password']) : '';
            $command = sprintf(
                'mysql --host=%s --port=%s --user=%s %s %s < %s 2>&1',
                escapeshellarg($config['host']),
                escapeshellarg($config['port']),
                escapeshellarg($config['username']),
                $passwordArg,
                escapeshellarg($config['database']),
                escapeshellarg($filepath)
            );

            // Exécuter la commande
            exec($command, $output, $returnCode);

            // Réactiver les contraintes de clés étrangères
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            if ($returnCode !== 0) {
                $error = implode("\n", $output);
                throw new Exception("Erreur lors de la restauration: {$error}");
            }

            Log::info("Sauvegarde restaurée avec succès depuis: {$filepath}");

            return [
                'success' => true,
                'message' => 'Base de données restaurée avec succès',
            ];
        } catch (Exception $e) {
            Log::error("Erreur lors de la restauration: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lister toutes les sauvegardes disponibles
     */
    public function listBackups()
    {
        $backups = [];
        $files = glob($this->backupPath . '/backup_*.sql');

        foreach ($files as $filepath) {
            $filename = basename($filepath);
            $metadataPath = str_replace('.sql', '.json', $filepath);
            
            $metadata = [
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath),
                'created_at' => date('Y-m-d H:i:s', filemtime($filepath)),
                'description' => null,
            ];

            if (file_exists($metadataPath)) {
                $savedMetadata = json_decode(file_get_contents($metadataPath), true);
                if ($savedMetadata) {
                    $metadata = array_merge($metadata, $savedMetadata);
                }
            }

            // Récupérer depuis la base de données si disponible
            try {
                if (DB::getSchemaBuilder()->hasTable('database_backups')) {
                    $dbBackup = DB::table('database_backups')
                        ->where('filename', $filename)
                        ->first();
                    
                    if ($dbBackup) {
                        $metadata['description'] = $dbBackup->description;
                        $metadata['id'] = $dbBackup->id;
                    }
                }
            } catch (Exception $e) {
                // Ignorer
            }

            $backups[] = $metadata;
        }

        // Trier par date de création (plus récent en premier)
        usort($backups, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return $backups;
    }

    /**
     * Supprimer une sauvegarde
     */
    public function deleteBackup($filename)
    {
        try {
            $filepath = $this->backupPath . '/' . $filename;
            $metadataPath = str_replace('.sql', '.json', $filepath);

            if (file_exists($filepath)) {
                unlink($filepath);
            }

            if (file_exists($metadataPath)) {
                unlink($metadataPath);
            }

            // Supprimer de la base de données si la table existe
            try {
                if (DB::getSchemaBuilder()->hasTable('database_backups')) {
                    DB::table('database_backups')
                        ->where('filename', $filename)
                        ->delete();
                }
            } catch (Exception $e) {
                // Ignorer
            }

            Log::info("Sauvegarde supprimée: {$filename}");

            return ['success' => true];
        } catch (Exception $e) {
            Log::error("Erreur lors de la suppression de la sauvegarde: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Télécharger une sauvegarde
     */
    public function downloadBackup($filename)
    {
        $filepath = $this->backupPath . '/' . $filename;
        
        if (!file_exists($filepath)) {
            throw new Exception("Le fichier de sauvegarde n'existe pas: {$filename}");
        }

        return $filepath;
    }

    /**
     * Obtenir les informations sur la base de données
     */
    public function getDatabaseInfo()
    {
        try {
            $config = config("database.connections.{$this->connection}");
            
            $tables = DB::select("SHOW TABLES");
            $tableNames = [];
            foreach ($tables as $table) {
                $tableNames[] = array_values((array)$table)[0];
            }

            $totalSize = 0;
            $tableInfo = [];
            
            foreach ($tableNames as $tableName) {
                // Utiliser des backticks pour échapper les mots réservés
                $result = DB::selectOne("
                    SELECT 
                        `table_name` AS `name`,
                        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS `size_mb`,
                        `table_rows` AS `row_count`
                    FROM information_schema.TABLES 
                    WHERE table_schema = ? AND table_name = ?
                ", [$config['database'], $tableName]);
                
                if ($result) {
                    $totalSize += $result->size_mb;
                    $tableInfo[] = [
                        'name' => $result->name,
                        'size_mb' => $result->size_mb,
                        'rows' => $result->row_count ?? 0,
                    ];
                }
            }

            return [
                'database' => $config['database'],
                'driver' => $config['driver'],
                'host' => $config['host'],
                'port' => $config['port'],
                'total_tables' => count($tableNames),
                'total_size_mb' => round($totalSize, 2),
                'tables' => $tableInfo,
            ];
        } catch (Exception $e) {
            Log::error("Erreur lors de la récupération des informations de la base de données: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Nettoyer les anciennes sauvegardes (garder seulement les N plus récentes)
     */
    public function cleanOldBackups($keep = 10)
    {
        try {
            $backups = $this->listBackups();
            
            if (count($backups) <= $keep) {
                return ['deleted' => 0, 'message' => 'Aucune sauvegarde à supprimer'];
            }

            $toDelete = array_slice($backups, $keep);
            $deleted = 0;

            foreach ($toDelete as $backup) {
                try {
                    $this->deleteBackup($backup['filename']);
                    $deleted++;
                } catch (Exception $e) {
                    Log::warning("Impossible de supprimer la sauvegarde {$backup['filename']}: " . $e->getMessage());
                }
            }

            return [
                'deleted' => $deleted,
                'message' => "{$deleted} sauvegarde(s) supprimée(s)",
            ];
        } catch (Exception $e) {
            Log::error("Erreur lors du nettoyage des sauvegardes: " . $e->getMessage());
            throw $e;
        }
    }
}
