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
     * Créer une sauvegarde de la base de données
     * 
     * @param string|null $description Description de la sauvegarde
     * @param string $type Type de sauvegarde: 'all' (tout), 'structure' (structure seule), 'data' (données seules)
     */
    public function createBackup($description = null, $type = 'all')
    {
        try {
            $config = config("database.connections.{$this->connection}");
            
            if (!in_array($config['driver'], ['mysql', 'mariadb'])) {
                throw new Exception("Le driver de base de données {$config['driver']} n'est pas supporté. Seuls MySQL et MariaDB sont supportés.");
            }

            // Valider le type
            if (!in_array($type, ['all', 'structure', 'data'])) {
                throw new Exception("Type de sauvegarde invalide. Utilisez 'all', 'structure' ou 'data'.");
            }

            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $typeSuffix = $type === 'all' ? 'full' : ($type === 'structure' ? 'structure' : 'data');
            $filename = "backup_{$typeSuffix}_{$timestamp}.sql";
            $filepath = $this->backupPath . '/' . $filename;

            // Construire la commande mysqldump selon le type
            $passwordArg = !empty($config['password']) ? '--password=' . escapeshellarg($config['password']) : '';
            
            // Options communes
            // Note: mysqldump génère déjà les tables dans un ordre qui respecte les dépendances
            // On réorganisera le fichier lors de la restauration pour garantir l'ordre
            $commonOptions = '--single-transaction --routines --triggers --lock-tables=false --skip-add-drop-table';
            
            // Options selon le type
            if ($type === 'structure') {
                // Structure seule : pas de données
                $dumpOptions = '--no-data';
            } elseif ($type === 'data') {
                // Données seules : pas de structure CREATE TABLE
                // --skip-triggers : Éviter les triggers pendant l'import des données
                $dumpOptions = '--no-create-info --insert-ignore --complete-insert --extended-insert --skip-triggers';
            } else {
                // Tout : structure + données
                // --skip-triggers : Éviter les triggers pendant l'import (on les réactivera après)
                $dumpOptions = '--insert-ignore --complete-insert --extended-insert --skip-triggers';
            }
            
            // Créer un fichier temporaire pour le dump brut
            $tempFile = $filepath . '.tmp';
            
            // Construire la commande mysqldump
            $command = sprintf(
                'mysqldump --host=%s --port=%s --user=%s %s %s %s %s > %s 2>&1',
                escapeshellarg($config['host']),
                escapeshellarg($config['port']),
                escapeshellarg($config['username']),
                $passwordArg,
                $commonOptions,
                $dumpOptions,
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
            
            // Modifier le fichier pour utiliser CREATE TABLE IF NOT EXISTS (seulement si structure présente)
            $content = file_get_contents($tempFile);
            
            // Remplacer CREATE TABLE par CREATE TABLE IF NOT EXISTS (seulement si on a la structure)
            if ($type !== 'data') {
                // Pattern: CREATE TABLE `nom_table` ( ou CREATE TABLE nom_table (
                $content = preg_replace('/CREATE TABLE\s+(`?)([^\s`\(]+)(`?)\s*\(/i', 'CREATE TABLE IF NOT EXISTS $1$2$3 (', $content);
            }
            
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
                'type' => $type,
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
                    'status' => 'reorganizing',
                    'message' => 'Réorganisation du fichier SQL pour respecter l\'ordre des dépendances...',
                    'progress' => 10,
                ]));
            }

            // Réorganiser le fichier SQL pour respecter l'ordre des dépendances
            $reorganizedFile = $this->reorganizeSqlFile($filepath);
            
            if ($progressFile) {
                file_put_contents($progressFile, json_encode([
                    'status' => 'restoring',
                    'message' => 'Restauration en cours...',
                    'progress' => 15,
                ]));
            }

            // Lire le fichier SQL pour l'exécuter via PDO
            $fileSize = filesize($reorganizedFile);
            
            if ($progressFile) {
                file_put_contents($progressFile, json_encode([
                    'status' => 'restoring',
                    'message' => 'Importation des données en cours...',
                    'progress' => 15,
                    'file_size' => $fileSize,
                ]));
            }

            Log::info("Restauration via PDO du fichier: {$reorganizedFile} ({$fileSize} bytes)");
            
            // Lire le contenu du fichier SQL
            $sqlContent = file_get_contents($reorganizedFile);
            
            if ($sqlContent === false) {
                throw new Exception("Impossible de lire le fichier SQL: {$reorganizedFile}");
            }
            
            if ($progressFile) {
                file_put_contents($progressFile, json_encode([
                    'status' => 'restoring',
                    'message' => 'Exécution des requêtes SQL...',
                    'progress' => 20,
                ]));
            }
            
            // Exécuter le SQL via PDO
            // On utilise DB::unprepared() qui permet d'exécuter plusieurs requêtes
            try {
                // Diviser le contenu en statements individuels pour un meilleur suivi
                $statements = $this->parseSqlStatements($sqlContent);
                $totalStatements = count($statements);
                $executedStatements = 0;
                $errors = [];
                
                Log::info("Nombre de statements SQL à exécuter: {$totalStatements}");
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    
                    // Ignorer les lignes vides et les commentaires
                    if (empty($statement) || strpos($statement, '--') === 0 || strpos($statement, '/*') === 0) {
                        continue;
                    }
                    
                    try {
                        DB::unprepared($statement);
                        $executedStatements++;
                        
                        // Mettre à jour la progression périodiquement
                        if ($progressFile && $executedStatements % 50 === 0) {
                            $progress = 20 + (int)(($executedStatements / $totalStatements) * 50);
                            file_put_contents($progressFile, json_encode([
                                'status' => 'restoring',
                                'message' => "Exécution des requêtes SQL... ({$executedStatements}/{$totalStatements})",
                                'progress' => min($progress, 70),
                                'executed_statements' => $executedStatements,
                                'total_statements' => $totalStatements,
                            ]));
                        }
                    } catch (\Exception $stmtError) {
                        // Logger l'erreur mais continuer (INSERT IGNORE, CREATE IF NOT EXISTS)
                        $shortStatement = substr($statement, 0, 100) . (strlen($statement) > 100 ? '...' : '');
                        Log::warning("Erreur SQL (ignorée): " . $stmtError->getMessage() . " - Statement: {$shortStatement}");
                        $errors[] = $stmtError->getMessage();
                    }
                }
                
                Log::info("Restauration terminée: {$executedStatements} statements exécutés, " . count($errors) . " erreurs");
                
            } catch (\Exception $e) {
                Log::error("Erreur fatale lors de la restauration SQL: " . $e->getMessage());
                
                // Nettoyer le fichier réorganisé
                if ($reorganizedFile !== $filepath && file_exists($reorganizedFile)) {
                    unlink($reorganizedFile);
                }
                
                if ($progressFile) {
                    file_put_contents($progressFile, json_encode([
                        'status' => 'error',
                        'message' => 'Erreur lors de l\'importation SQL',
                        'error' => $e->getMessage(),
                        'progress' => 0,
                    ]));
                }
                
                throw new Exception("Erreur lors de l'exécution SQL: " . $e->getMessage());
            }
            
            // Nettoyer le fichier réorganisé si c'était un fichier temporaire
            if ($reorganizedFile !== $filepath && file_exists($reorganizedFile)) {
                unlink($reorganizedFile);
            }
            
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
        // Rechercher tous les fichiers de sauvegarde (anciens et nouveaux formats)
        $files = array_merge(
            glob($this->backupPath . '/backup_*.sql'),
            glob($this->backupPath . '/backup_full_*.sql'),
            glob($this->backupPath . '/backup_structure_*.sql'),
            glob($this->backupPath . '/backup_data_*.sql')
        );
        $files = array_unique($files); // Éviter les doublons

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
            
            // Détecter le type depuis le nom du fichier si non présent dans les métadonnées
            if (!isset($metadata['type'])) {
                if (strpos($filename, 'backup_full_') !== false || strpos($filename, 'backup_') === 0 && strpos($filename, 'backup_full_') === false && strpos($filename, 'backup_structure_') === false && strpos($filename, 'backup_data_') === false) {
                    // Ancien format ou format "full" - analyser le contenu
                    $sample = file_get_contents($filepath, false, null, 0, min(100000, filesize($filepath)));
                    $hasData = (strpos($sample, 'INSERT') !== false || strpos($sample, 'VALUES') !== false);
                    $hasStructure = (strpos($sample, 'CREATE TABLE') !== false);
                    
                    if ($hasStructure && $hasData) {
                        $metadata['type'] = 'all';
                    } elseif ($hasStructure && !$hasData) {
                        $metadata['type'] = 'structure';
                    } elseif (!$hasStructure && $hasData) {
                        $metadata['type'] = 'data';
                    } else {
                        $metadata['type'] = 'all'; // Par défaut
                    }
                } elseif (strpos($filename, 'backup_structure_') !== false) {
                    $metadata['type'] = 'structure';
                } elseif (strpos($filename, 'backup_data_') !== false) {
                    $metadata['type'] = 'data';
                } else {
                    $metadata['type'] = 'all'; // Par défaut pour les anciennes sauvegardes
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
     * Réorganiser un fichier SQL pour respecter l'ordre des dépendances
     * - D'abord toutes les structures (CREATE TABLE) dans le bon ordre
     * - Ensuite toutes les données (INSERT) dans le bon ordre (tables référencées en premier)
     */
    protected function reorganizeSqlFile($filepath)
    {
        Log::info("Réorganisation du fichier SQL: {$filepath}");
        
        $content = file_get_contents($filepath);
        $contentLength = strlen($content);
        
        Log::info("Taille du fichier SQL: {$contentLength} bytes");
        
        // Si le fichier est très petit, ne pas le modifier
        if ($contentLength < 1000) {
            Log::info("Fichier trop petit, pas de réorganisation nécessaire");
            return $filepath;
        }
        
        // Compter les INSERT et CREATE TABLE pour voir si on a des données
        $insertCount = substr_count(strtoupper($content), 'INSERT');
        $createCount = substr_count(strtoupper($content), 'CREATE TABLE');
        
        Log::info("Analyse du fichier SQL", [
            'insert_count' => $insertCount,
            'create_count' => $createCount,
        ]);
        
        // Si le fichier ne contient que des INSERT (pas de structure), ne pas le modifier
        // car les tables doivent déjà exister
        if ($createCount === 0 && $insertCount > 0) {
            Log::info("Fichier de données uniquement, pas de réorganisation nécessaire");
            
            // Mais on doit quand même désactiver les clés étrangères
            $tempFile = $filepath . '.prepared.' . time();
            $preparedContent = "SET FOREIGN_KEY_CHECKS=0;\n\n" . $content . "\n\nSET FOREIGN_KEY_CHECKS=1;\n";
            file_put_contents($tempFile, $preparedContent);
            
            Log::info("Fichier préparé avec FOREIGN_KEY_CHECKS: {$tempFile}");
            return $tempFile;
        }
        
        // Si pas d'INSERT, c'est un fichier de structure uniquement, ne pas modifier
        if ($insertCount === 0) {
            Log::info("Fichier de structure uniquement, pas de réorganisation nécessaire");
            return $filepath;
        }
        
        // Sinon, on a un fichier mixte (structure + données)
        // Le fichier est probablement déjà dans le bon ordre (mysqldump le fait)
        // On va juste s'assurer que FOREIGN_KEY_CHECKS est désactivé
        
        // Vérifier si FOREIGN_KEY_CHECKS est déjà présent
        if (stripos($content, 'FOREIGN_KEY_CHECKS=0') !== false) {
            Log::info("FOREIGN_KEY_CHECKS déjà présent dans le fichier, pas de modification");
            return $filepath;
        }
        
        // Ajouter FOREIGN_KEY_CHECKS au début et à la fin
        $tempFile = $filepath . '.prepared.' . time();
        $preparedContent = "SET FOREIGN_KEY_CHECKS=0;\n\n" . $content . "\n\nSET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($tempFile, $preparedContent);
        
        Log::info("Fichier préparé avec FOREIGN_KEY_CHECKS: {$tempFile}", [
            'original_size' => $contentLength,
            'new_size' => strlen($preparedContent),
        ]);
        
        return $tempFile;
    }
    
    /**
     * Ancienne méthode de réorganisation complète (conservée pour référence mais non utilisée)
     * Cette méthode était trop agressive et pouvait corrompre le fichier SQL
     */
    protected function reorganizeSqlFileComplex($filepath)
    {
        $content = file_get_contents($filepath);
        
        // Si le fichier est très petit, ne pas le modifier
        if (strlen($content) < 1000) {
            return $filepath;
        }
        
        // Séparer le contenu en sections
        $structureStatements = [];
        $dataStatements = [];
        $otherStatements = [];
        
        // Extraire les CREATE TABLE (avec ou sans IF NOT EXISTS)
        preg_match_all('/CREATE TABLE (IF NOT EXISTS )?[^;]+;/is', $content, $createMatches);
        $structureStatements = $createMatches[0] ?? [];
        
        // Extraire les INSERT (avec ou sans IGNORE)
        // Pattern amélioré pour gérer les INSERT multi-lignes avec VALUES multiples
        // On capture tout jusqu'au point-virgule, même sur plusieurs lignes
        preg_match_all('/INSERT\s+(?:IGNORE\s+)?INTO[^;]*(?:VALUES[^;]*)?;/is', $content, $insertMatches);
        $dataStatements = $insertMatches[0] ?? [];
        
        // Si on n'a pas trouvé beaucoup d'INSERT, essayer un pattern plus large
        if (count($dataStatements) < 5 && strpos($content, 'INSERT') !== false) {
            // Essayer de capturer les INSERT qui peuvent être sur plusieurs lignes
            $lines = explode("\n", $content);
            $currentInsert = '';
            $inInsert = false;
            
            foreach ($lines as $line) {
                if (preg_match('/INSERT\s+(?:IGNORE\s+)?INTO/i', $line)) {
                    $inInsert = true;
                    $currentInsert = $line;
                } elseif ($inInsert) {
                    $currentInsert .= "\n" . $line;
                    if (strpos($line, ';') !== false) {
                        $dataStatements[] = $currentInsert;
                        $currentInsert = '';
                        $inInsert = false;
                    }
                }
            }
            
            // Ajouter le dernier INSERT si pas terminé
            if ($inInsert && !empty($currentInsert)) {
                $dataStatements[] = $currentInsert . ';';
            }
        }
        
        // Extraire les autres statements (SET, USE, LOCK, etc.)
        preg_match_all('/(SET|USE|LOCK|UNLOCK|DROP|ALTER)[^;]*;/is', $content, $otherMatches);
        $otherStatements = $otherMatches[0] ?? [];
        
        // Si pas assez de contenu, ne pas réorganiser
        if (count($structureStatements) === 0 && count($dataStatements) === 0) {
            return $filepath;
        }
        
        // Créer un fichier temporaire réorganisé
        $reorganizedFile = $filepath . '.reorganized.' . time();
        $reorganizedContent = '';
        
        // 1. Ajouter les statements de configuration en premier (USE, SET, etc.)
        foreach ($otherStatements as $stmt) {
            $stmtUpper = strtoupper($stmt);
            if (stripos($stmtUpper, 'USE ') !== false || 
                stripos($stmtUpper, 'SET ') !== false ||
                stripos($stmtUpper, 'LOCK') !== false ||
                stripos($stmtUpper, 'UNLOCK') !== false) {
                $reorganizedContent .= trim($stmt) . "\n";
            }
        }
        
        // 2. Désactiver les clés étrangères
        $reorganizedContent .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        // 3. Ajouter toutes les structures (CREATE TABLE) dans l'ordre original
        // mysqldump génère déjà dans le bon ordre
        foreach ($structureStatements as $stmt) {
            $reorganizedContent .= trim($stmt) . "\n\n";
        }
        
        // 4. Ajouter toutes les données (INSERT) dans le bon ordre
        // Trier les INSERT pour mettre les tables référencées en premier
        $sortedData = $this->sortInsertsByDependencies($dataStatements);
        foreach ($sortedData as $stmt) {
            $reorganizedContent .= trim($stmt) . "\n";
        }
        
        // 5. Réactiver les clés étrangères
        $reorganizedContent .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
        
        // Écrire le fichier réorganisé
        file_put_contents($reorganizedFile, $reorganizedContent);
        
        Log::info("Fichier SQL réorganisé pour respecter l'ordre des dépendances", [
            'original' => $filepath,
            'reorganized' => $reorganizedFile,
            'structures' => count($structureStatements),
            'inserts' => count($dataStatements),
        ]);
        
        return $reorganizedFile;
    }
    
    /**
     * Trier les INSERT par dépendances (tables référencées en premier)
     * L'ordre est crucial : les tables qui sont référencées par d'autres doivent être remplies en premier
     * 
     * Stratégie :
     * 1. Tables système et de configuration en premier
     * 2. Tables utilisateurs et authentification
     * 3. Tables de référence (catégories, types, etc.)
     * 4. Tables métier de base
     * 5. Tables de relations et jointures
     * 6. Tables de logs et historique
     */
    protected function sortInsertsByDependencies($insertStatements)
    {
        if (empty($insertStatements)) {
            return [];
        }
        
        // Grouper les INSERT par table
        $insertsByTable = [];
        $tableNames = [];
        
        foreach ($insertStatements as $insert) {
            // Extraire le nom de la table (gérer les backticks et les formats variés)
            // Pattern: INSERT [IGNORE] INTO `table` ou INSERT [IGNORE] INTO table
            if (preg_match('/INSERT\s+(?:IGNORE\s+)?INTO\s+[`]?([^\s`\(]+)[`]?/i', $insert, $matches)) {
                $tableName = strtolower(trim($matches[1]));
                if (!isset($insertsByTable[$tableName])) {
                    $insertsByTable[$tableName] = [];
                    $tableNames[] = $tableName;
                }
                $insertsByTable[$tableName][] = $insert;
            } else {
                // Si on ne peut pas extraire le nom, garder l'ordre original
                if (!isset($insertsByTable['_unknown'])) {
                    $insertsByTable['_unknown'] = [];
                }
                $insertsByTable['_unknown'][] = $insert;
            }
        }
        
        // Ordre de priorité : tables qui sont généralement référencées par d'autres
        // Ces tables doivent être remplies EN PREMIER pour éviter les erreurs de clés étrangères
        $priorityOrder = [
            // Niveau 1: Tables système (souvent référencées mais ne référencent rien)
            'migrations',
            'settings', 'config', 'configuration',
            'database_backups',
            
            // Niveau 2: Tables utilisateurs et authentification (référencées par beaucoup de tables)
            'users', 'user',
            'roles', 'role',
            'permissions', 'permission',
            'model_has_permissions', 'model_has_roles',
            'personal_access_tokens',
            
            // Niveau 3: Tables de référence/catégories (souvent référencées)
            'categories', 'category',
            'types', 'type',
            'status', 'statuses',
            'countries', 'country',
            'cities', 'city',
            
            // Niveau 4: Tables métier de base (peuvent référencer les niveaux précédents)
            'entreprises', 'entreprise',
            'services', 'service',
            'produits', 'produit',
            'reservations', 'reservation',
            
            // Niveau 5: Tables de relations (référencent plusieurs tables)
            'entreprise_membres', 'entreprise_membre',
            'user_entreprises', 'user_entreprise',
            'reservation_services', 'reservation_service',
        ];
        
        $sorted = [];
        $processed = [];
        
        // 1. Ajouter les tables prioritaires dans l'ordre défini
        foreach ($priorityOrder as $priorityTable) {
            foreach ($tableNames as $tableName) {
                // Correspondance exacte ou partielle (pour gérer les pluriels et variations)
                $isMatch = ($tableName === $priorityTable) ||
                          (strpos($tableName, $priorityTable) !== false) ||
                          (strpos($priorityTable, $tableName) !== false);
                
                if ($isMatch && !isset($processed[$tableName])) {
                    $sorted = array_merge($sorted, $insertsByTable[$tableName]);
                    $processed[$tableName] = true;
                    Log::debug("Table prioritaire ajoutée: {$tableName}");
                }
            }
        }
        
        // 2. Ajouter les autres tables dans l'ordre alphabétique
        // (pour avoir un ordre déterministe et reproductible)
        $remainingTables = array_diff($tableNames, array_keys($processed));
        sort($remainingTables);
        
        foreach ($remainingTables as $tableName) {
            if (!isset($processed[$tableName])) {
                $sorted = array_merge($sorted, $insertsByTable[$tableName]);
                Log::debug("Table ajoutée (ordre alphabétique): {$tableName}");
            }
        }
        
        // 3. Ajouter les INSERT non identifiés à la fin
        if (isset($insertsByTable['_unknown'])) {
            $sorted = array_merge($sorted, $insertsByTable['_unknown']);
            Log::warning("INSERT non identifiés ajoutés à la fin du fichier réorganisé");
        }
        
        Log::info("INSERT réorganisés", [
            'total' => count($insertStatements),
            'tables_prioritaires' => count($processed),
            'tables_autres' => count($remainingTables),
        ]);
        
        return $sorted;
    }

    /**
     * Parser un fichier SQL en statements individuels
     * Gère les cas complexes comme les INSERT multi-lignes, les commentaires, etc.
     */
    protected function parseSqlStatements($sqlContent)
    {
        $statements = [];
        $currentStatement = '';
        $inString = false;
        $stringChar = '';
        $inComment = false;
        $commentType = '';
        
        $length = strlen($sqlContent);
        
        for ($i = 0; $i < $length; $i++) {
            $char = $sqlContent[$i];
            $nextChar = ($i + 1 < $length) ? $sqlContent[$i + 1] : '';
            
            // Gestion des commentaires sur une ligne (--)
            if (!$inString && !$inComment && $char === '-' && $nextChar === '-') {
                $inComment = true;
                $commentType = '--';
                $currentStatement .= $char;
                continue;
            }
            
            // Gestion des commentaires multi-lignes /* */
            if (!$inString && !$inComment && $char === '/' && $nextChar === '*') {
                $inComment = true;
                $commentType = '/*';
                $currentStatement .= $char;
                continue;
            }
            
            // Fin de commentaire sur une ligne
            if ($inComment && $commentType === '--' && ($char === "\n" || $char === "\r")) {
                $inComment = false;
                $commentType = '';
                $currentStatement .= $char;
                continue;
            }
            
            // Fin de commentaire multi-lignes
            if ($inComment && $commentType === '/*' && $char === '*' && $nextChar === '/') {
                $inComment = false;
                $commentType = '';
                $currentStatement .= $char . $nextChar;
                $i++; // Sauter le /
                continue;
            }
            
            // Dans un commentaire, continuer
            if ($inComment) {
                $currentStatement .= $char;
                continue;
            }
            
            // Gestion des chaînes de caractères
            if (!$inString && ($char === "'" || $char === '"')) {
                $inString = true;
                $stringChar = $char;
                $currentStatement .= $char;
                continue;
            }
            
            // Fin de chaîne (gérer les échappements)
            if ($inString && $char === $stringChar) {
                // Vérifier si c'est un échappement ('')
                if ($nextChar === $stringChar) {
                    $currentStatement .= $char . $nextChar;
                    $i++; // Sauter le caractère suivant
                    continue;
                }
                // Vérifier si c'est un échappement avec backslash
                $prevChar = ($i > 0) ? $sqlContent[$i - 1] : '';
                if ($prevChar === '\\') {
                    $currentStatement .= $char;
                    continue;
                }
                $inString = false;
                $stringChar = '';
                $currentStatement .= $char;
                continue;
            }
            
            // Dans une chaîne, ajouter le caractère
            if ($inString) {
                $currentStatement .= $char;
                continue;
            }
            
            // Détection du délimiteur (;)
            if ($char === ';') {
                $currentStatement .= $char;
                $trimmed = trim($currentStatement);
                
                // Ignorer les statements vides ou les commentaires seuls
                if (!empty($trimmed) && 
                    strpos($trimmed, '--') !== 0 && 
                    !(strpos($trimmed, '/*') === 0 && strpos($trimmed, '*/') === strlen($trimmed) - 2)) {
                    $statements[] = $trimmed;
                }
                
                $currentStatement = '';
                continue;
            }
            
            $currentStatement .= $char;
        }
        
        // Ajouter le dernier statement s'il n'est pas vide
        $trimmed = trim($currentStatement);
        if (!empty($trimmed) && $trimmed !== ';') {
            $statements[] = $trimmed;
        }
        
        return $statements;
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
