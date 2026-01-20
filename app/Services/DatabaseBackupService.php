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
            // Options importantes pour récupération d'urgence :
            // --single-transaction : Pour une sauvegarde cohérente sans verrous
            // --routines : Inclure les procédures stockées et fonctions
            // --triggers : Inclure les triggers
            // --insert-ignore : Utiliser INSERT IGNORE pour éviter les erreurs de doublons
            // --complete-insert : INSERT complets avec noms de colonnes (plus sûr pour restauration)
            // --extended-insert : INSERT optimisés (plus rapide)
            // --lock-tables=false : Pas de verrous (déjà géré par --single-transaction)
            // --skip-add-drop-table : Ne pas ajouter DROP TABLE (on utilisera CREATE TABLE IF NOT EXISTS)
            // Par défaut, mysqldump inclut TOUTES les données (structure + données + relations)
            $passwordArg = !empty($config['password']) ? '--password=' . escapeshellarg($config['password']) : '';
            
            // Créer un fichier temporaire pour le dump brut
            $tempFile = $filepath . '.tmp';
            
            // D'abord, créer le dump avec structure et données
            $command = sprintf(
                'mysqldump --host=%s --port=%s --user=%s %s --single-transaction --routines --triggers --insert-ignore --complete-insert --extended-insert --lock-tables=false --skip-add-drop-table %s > %s 2>&1',
                escapeshellarg($config['host']),
                escapeshellarg($config['port']),
                escapeshellarg($config['username']),
                $passwordArg,
                escapeshellarg($config['database']),
                escapeshellarg($tempFile)
            );
            
            // Exécuter la commande
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0 || !file_exists($tempFile)) {
                $error = implode("\n", $output);
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
                throw new Exception("Erreur lors de la création de la sauvegarde: {$error}");
            }
            
            // Modifier le fichier pour utiliser CREATE TABLE IF NOT EXISTS
            $content = file_get_contents($tempFile);
            
            // Remplacer CREATE TABLE par CREATE TABLE IF NOT EXISTS
            // Pattern: CREATE TABLE `nom_table` ( ou CREATE TABLE nom_table (
            $content = preg_replace('/CREATE TABLE\s+(`?)([^\s`\(]+)(`?)\s*\(/i', 'CREATE TABLE IF NOT EXISTS $1$2$3 (', $content);
            
            // Écrire le fichier modifié
            file_put_contents($filepath, $content);
            
            // Supprimer le fichier temporaire
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }

            // Vérifier que le fichier n'est pas vide
            if (filesize($filepath) === 0) {
                unlink($filepath);
                throw new Exception("La sauvegarde est vide. Vérifiez les permissions et les identifiants de la base de données.");
            }

            // Vérifier que le fichier contient bien des données (INSERT statements)
            $fileContent = file_get_contents($filepath, false, null, 0, 10000); // Lire les 10 premiers KB
            if (strpos($fileContent, 'INSERT IGNORE INTO') === false && strpos($fileContent, 'INSERT') === false) {
                // Le fichier pourrait ne contenir que la structure, vérifier plus en profondeur
                $fullContent = file_get_contents($filepath);
                if (strpos($fullContent, 'INSERT') === false && strpos($fullContent, 'VALUES') === false) {
                    Log::warning("La sauvegarde semble ne contenir que la structure, pas de données détectées");
                    // Ne pas échouer, car certaines bases peuvent être vides
                }
            }
            
            // Vérifier que CREATE TABLE IF NOT EXISTS est bien présent
            if (strpos($fileContent, 'CREATE TABLE IF NOT EXISTS') === false && strpos($fullContent ?? $fileContent, 'CREATE TABLE IF NOT EXISTS') === false) {
                Log::warning("CREATE TABLE IF NOT EXISTS non détecté dans la sauvegarde, vérifiez le fichier");
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
    public function restoreBackup($filepath, $confirm = false, $progressFile = null)
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

            // Créer un fichier de progression si fourni
            if ($progressFile) {
                file_put_contents($progressFile, json_encode([
                    'status' => 'starting',
                    'message' => 'Démarrage de la restauration...',
                    'progress' => 0,
                ]));
            }

            // Vérifier que le fichier contient des données
            $fileSize = filesize($filepath);
            $sample = file_get_contents($filepath, false, null, 0, min(50000, $fileSize));
            
            $hasData = (strpos($sample, 'INSERT') !== false || strpos($sample, 'VALUES') !== false);
            $hasStructure = (strpos($sample, 'CREATE TABLE') !== false);
            
            // Vérifier si la sauvegarde contient des utilisateurs
            $hasUsers = false;
            $userCount = 0;
            $hasDeleteOrTruncate = false;
            if ($hasData) {
                // Lire plus de contenu pour chercher la table users et les commandes destructives
                $largerSample = file_get_contents($filepath, false, null, 0, min(200000, $fileSize));
                
                // Vérifier les commandes destructives
                if (preg_match('/(DELETE\s+FROM|TRUNCATE\s+TABLE|DROP\s+TABLE)\s+[^;]*users?/i', $largerSample)) {
                    $hasDeleteOrTruncate = true;
                    Log::warning("La sauvegarde contient des commandes DELETE/TRUNCATE/DROP sur la table users");
                }
                
                // Chercher les INSERT dans la table users
                if (preg_match('/INSERT[^;]*INTO[^`]*`?users?`?[^;]*VALUES[^;]*/i', $largerSample, $matches)) {
                    $hasUsers = true;
                    // Compter approximativement le nombre d'utilisateurs
                    $userCount = substr_count($matches[0], 'VALUES') + substr_count($matches[0], '),(');
                }
            }
            
            if ($progressFile) {
                $analysisMessage = 'Analyse du fichier de sauvegarde...';
                if ($hasStructure && !$hasUsers && !$hasDeleteOrTruncate) {
                    $analysisMessage .= ' ⚠️ Aucun utilisateur détecté dans la sauvegarde.';
                }
                if ($hasDeleteOrTruncate) {
                    $analysisMessage .= ' ⚠️ ATTENTION: Commandes destructives détectées !';
                }
                
                file_put_contents($progressFile, json_encode([
                    'status' => 'analyzing',
                    'message' => $analysisMessage,
                    'has_data' => $hasData,
                    'has_structure' => $hasStructure,
                    'has_users' => $hasUsers,
                    'has_destructive_commands' => $hasDeleteOrTruncate,
                    'estimated_user_count' => $userCount,
                    'file_size' => $fileSize,
                    'progress' => 5,
                ]));
            }

            if (!$hasStructure && !$hasData) {
                throw new Exception("Le fichier de sauvegarde semble vide ou invalide.");
            }
            
            // Avertir si la sauvegarde ne contient pas d'utilisateurs
            if ($hasStructure && !$hasUsers && !$hasDeleteOrTruncate) {
                Log::warning("Restauration d'une sauvegarde qui ne semble pas contenir d'utilisateurs. Les utilisateurs existants devraient être préservés grâce à INSERT IGNORE.");
            }
            
            // Avertir si des commandes destructives sont présentes
            if ($hasDeleteOrTruncate) {
                Log::critical("Restauration d'une sauvegarde contenant des commandes DELETE/TRUNCATE/DROP. Les données existantes pourraient être supprimées !");
            }

            // Désactiver les contraintes de clés étrangères temporairement
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            if ($progressFile) {
                file_put_contents($progressFile, json_encode([
                    'status' => 'restoring',
                    'message' => 'Restauration en cours...',
                    'progress' => 10,
                ]));
            }

            // Construire la commande mysql pour restaurer
            // Utiliser --password= pour éviter l'invite interactive si le mot de passe est vide
            $passwordArg = !empty($config['password']) ? '--password=' . escapeshellarg($config['password']) : '';
            
            // Lire le fichier pour estimer la progression
            $fileSize = filesize($filepath);
            $estimatedProgress = 10; // Début à 10%
            
            if ($progressFile) {
                file_put_contents($progressFile, json_encode([
                    'status' => 'restoring',
                    'message' => 'Importation des données en cours...',
                    'progress' => $estimatedProgress,
                    'file_size' => $fileSize,
                ]));
            }

            // Exécuter la commande mysql pour restaurer
            // Note: mysql lit le fichier ligne par ligne, donc on ne peut pas vraiment suivre la progression
            // Mais on peut estimer en fonction de la taille du fichier
            $command = sprintf(
                'mysql --host=%s --port=%s --user=%s %s %s < %s 2>&1',
                escapeshellarg($config['host']),
                escapeshellarg($config['port']),
                escapeshellarg($config['username']),
                $passwordArg,
                escapeshellarg($config['database']),
                escapeshellarg($filepath)
            );

            // Mettre à jour la progression avant l'exécution
            if ($progressFile) {
                file_put_contents($progressFile, json_encode([
                    'status' => 'restoring',
                    'message' => 'Importation des données... (cela peut prendre plusieurs minutes)',
                    'progress' => 30,
                ]));
            }

            // Exécuter la commande et capturer la sortie
            exec($command, $output, $returnCode);
            
            // Mettre à jour la progression après l'exécution
            if ($progressFile) {
                file_put_contents($progressFile, json_encode([
                    'status' => 'restoring',
                    'message' => 'Importation terminée, vérification en cours...',
                    'progress' => 70,
                ]));
            }

            // Réactiver les contraintes de clés étrangères
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            if ($progressFile) {
                file_put_contents($progressFile, json_encode([
                    'status' => 'verifying',
                    'message' => 'Vérification des données importées...',
                    'progress' => 90,
                ]));
            }

            // Vérifier que des données ont été importées
            $tables = DB::select("SHOW TABLES");
            $totalRows = 0;
            $tableDetails = [];
            $usersCount = 0;
            
            if ($progressFile) {
                file_put_contents($progressFile, json_encode([
                    'status' => 'counting',
                    'message' => 'Comptage des données importées...',
                    'progress' => 85,
                ]));
            }
            
            foreach ($tables as $table) {
                $tableName = array_values((array)$table)[0];
                try {
                    $count = DB::table($tableName)->count();
                    $totalRows += $count;
                    if ($count > 0) {
                        $tableDetails[] = [
                            'name' => $tableName,
                            'rows' => $count,
                        ];
                    }
                    // Compter spécifiquement les utilisateurs
                    if (strtolower($tableName) === 'users') {
                        $usersCount = $count;
                    }
                } catch (\Exception $e) {
                    // Ignorer les erreurs de comptage
                }
            }

            if ($returnCode !== 0) {
                $error = implode("\n", $output);
                if ($progressFile) {
                    file_put_contents($progressFile, json_encode([
                        'status' => 'error',
                        'message' => 'Erreur lors de la restauration',
                        'error' => $error,
                        'progress' => 0,
                    ]));
                }
                throw new Exception("Erreur lors de la restauration: {$error}");
            }

            if ($progressFile) {
                $message = '✅ Restauration terminée avec succès !';
                if ($usersCount === 0) {
                    $message .= ' ⚠️ ATTENTION: Aucun utilisateur trouvé dans la base de données après restauration.';
                }
                
                file_put_contents($progressFile, json_encode([
                    'status' => 'completed',
                    'message' => $message,
                    'total_tables' => count($tables),
                    'total_rows' => $totalRows,
                    'users_count' => $usersCount,
                    'tables_with_data' => count($tableDetails),
                    'table_details' => array_slice($tableDetails, 0, 10), // Premières 10 tables avec données
                    'progress' => 100,
                ]));
            }

            Log::info("Sauvegarde restaurée avec succès depuis: {$filepath}", [
                'total_tables' => count($tables),
                'total_rows' => $totalRows,
            ]);

            $message = 'Base de données restaurée avec succès';
            if ($usersCount === 0) {
                $message .= '. ⚠️ ATTENTION: Aucun utilisateur trouvé dans la base de données.';
            }
            
            return [
                'success' => true,
                'message' => $message,
                'total_tables' => count($tables),
                'total_rows' => $totalRows,
                'users_count' => $usersCount,
                'tables_with_data' => count($tableDetails),
                'table_details' => array_slice($tableDetails, 0, 10),
            ];
        } catch (Exception $e) {
            Log::error("Erreur lors de la restauration: " . $e->getMessage());
            if ($progressFile && file_exists($progressFile)) {
                file_put_contents($progressFile, json_encode([
                    'status' => 'error',
                    'message' => 'Erreur lors de la restauration',
                    'error' => $e->getMessage(),
                    'progress' => 0,
                ]));
            }
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
     * Récupérer les données d'une table spécifique
     */
    public function getTableData($tableName, $page = 1, $perPage = 50)
    {
        try {
            $config = config("database.connections.{$this->connection}");
            
            // Vérifier que la table existe
            $tableExists = DB::selectOne("
                SELECT COUNT(*) as count 
                FROM information_schema.TABLES 
                WHERE table_schema = ? AND table_name = ?
            ", [$config['database'], $tableName]);
            
            if (!$tableExists || $tableExists->count == 0) {
                throw new Exception("La table {$tableName} n'existe pas");
            }
            
            // Compter le total de lignes
            $total = DB::table($tableName)->count();
            
            // Récupérer les données paginées
            $offset = ($page - 1) * $perPage;
            $data = DB::table($tableName)
                ->limit($perPage)
                ->offset($offset)
                ->get();
            
            // Récupérer les colonnes de la table
            $columns = DB::select("SHOW COLUMNS FROM `{$tableName}`");
            
            return [
                'data' => $data,
                'columns' => $columns,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($total / $perPage),
            ];
        } catch (Exception $e) {
            Log::error("Erreur lors de la récupération des données de la table {$tableName}: " . $e->getMessage());
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
