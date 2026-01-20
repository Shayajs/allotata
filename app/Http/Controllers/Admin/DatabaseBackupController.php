<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DatabaseBackupController extends Controller
{
    protected $backupService;

    public function __construct(DatabaseBackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Afficher la page principale de gestion des sauvegardes
     */
    public function index(Request $request)
    {
        $error = null;
        
        try {
            $backups = $this->backupService->listBackups();
            $dbInfo = $this->backupService->getDatabaseInfo();
        } catch (\Exception $e) {
            $backups = [];
            $dbInfo = null;
            $error = 'Erreur lors du chargement des informations: ' . $e->getMessage();
            Log::error("Erreur dans DatabaseBackupController::index: " . $e->getMessage());
        }

        return view('admin.database.index', compact('backups', 'dbInfo', 'error'));
    }

    /**
     * Créer une sauvegarde manuelle
     */
    public function create(Request $request)
    {
        $request->validate([
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $result = $this->backupService->createBackup($request->description);
            
            return response()->json([
                'success' => true,
                'message' => 'Sauvegarde créée avec succès',
                'backup' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur lors de la création de la sauvegarde: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la sauvegarde: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Télécharger une sauvegarde
     */
    public function download($filename)
    {
        try {
            $filepath = $this->backupService->downloadBackup($filename);
            
            return response()->download($filepath, $filename);
        } catch (\Exception $e) {
            Log::error("Erreur lors du téléchargement de la sauvegarde: " . $e->getMessage());
            
            // Rediriger avec un message d'erreur dans l'URL si la session n'est pas disponible
            return redirect()->route('admin.database.index')
                ->with('error', 'Erreur lors du téléchargement: ' . $e->getMessage());
        }
    }

    /**
     * Restaurer une sauvegarde
     */
    public function restore(Request $request, $filename)
    {
        $request->validate([
            'confirm' => 'required|accepted',
        ]);

        try {
            $filepath = storage_path('app/backups/database/' . $filename);
            
            if (!file_exists($filepath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le fichier de sauvegarde n\'existe pas',
                ], 404);
            }

            $result = $this->backupService->restoreBackup($filepath, true);
            
            return response()->json([
                'success' => true,
                'message' => 'Base de données restaurée avec succès',
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur lors de la restauration: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la restauration: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer une sauvegarde
     */
    public function destroy($filename)
    {
        try {
            $this->backupService->deleteBackup($filename);
            
            return response()->json([
                'success' => true,
                'message' => 'Sauvegarde supprimée avec succès',
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur lors de la suppression: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir les informations sur la base de données
     */
    public function getDatabaseInfo()
    {
        try {
            $info = $this->backupService->getDatabaseInfo();
            
            return response()->json([
                'success' => true,
                'data' => $info,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des informations: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Nettoyer les anciennes sauvegardes
     */
    public function clean(Request $request)
    {
        $request->validate([
            'keep' => 'required|integer|min:1|max:100',
        ]);

        try {
            $result = $this->backupService->cleanOldBackups($request->keep);
            
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'deleted' => $result['deleted'],
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur lors du nettoyage: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du nettoyage: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Importer une sauvegarde depuis un fichier uploadé
     */
    public function import(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:sql,gz|max:102400', // Max 100MB
        ]);

        try {
            $file = $request->file('backup_file');
            $originalName = $file->getClientOriginalName();
            
            // Déplacer le fichier vers le dossier de sauvegardes
            $backupPath = storage_path('app/backups/database');
            if (!file_exists($backupPath)) {
                mkdir($backupPath, 0755, true);
            }
            
            $filename = 'imported_' . time() . '_' . $originalName;
            $filepath = $backupPath . '/' . $filename;
            
            $file->move($backupPath, $filename);
            
            // Si c'est un fichier .gz, le décompresser
            if (pathinfo($filename, PATHINFO_EXTENSION) === 'gz') {
                $gz = gzopen($filepath, 'rb');
                $sqlFilepath = str_replace('.gz', '', $filepath);
                $sqlFile = fopen($sqlFilepath, 'wb');
                
                while (!gzeof($gz)) {
                    fwrite($sqlFile, gzread($gz, 4096));
                }
                
                fclose($sqlFile);
                gzclose($gz);
                unlink($filepath);
                
                $filepath = $sqlFilepath;
                $filename = basename($sqlFilepath);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Fichier importé avec succès',
                'filename' => $filename,
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'import: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'import: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Déclencher une sauvegarde automatique via HTTP (pour Docker/cron externe)
     * Route publique mais protégée par token secret
     */
    public function autoBackup(Request $request)
    {
        // Vérifier le token secret
        $secretToken = env('AUTO_BACKUP_TOKEN', 'change-me-in-production');
        $providedToken = $request->get('token') ?? $request->header('X-Backup-Token');
        
        if (empty($secretToken) || $secretToken === 'change-me-in-production') {
            Log::warning('AUTO_BACKUP_TOKEN non configuré dans .env');
            return response()->json([
                'success' => false,
                'message' => 'Token de sauvegarde automatique non configuré',
            ], 500);
        }
        
        if ($providedToken !== $secretToken) {
            Log::warning('Tentative d\'accès à la route de sauvegarde automatique avec un token invalide', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Token invalide',
            ], 403);
        }

        try {
            $keep = (int) ($request->get('keep') ?? 30);
            $description = $request->get('description', 'Sauvegarde automatique');
            
            // Créer la sauvegarde
            $result = $this->backupService->createBackup($description);
            
            // Nettoyer les anciennes sauvegardes
            $cleanResult = $this->backupService->cleanOldBackups($keep);
            
            Log::info("Sauvegarde automatique créée via HTTP", [
                'filename' => $result['filename'],
                'size' => $result['size'],
                'deleted' => $cleanResult['deleted'] ?? 0,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Sauvegarde créée avec succès',
                'backup' => [
                    'filename' => $result['filename'],
                    'size' => $result['size'],
                    'created_at' => $result['metadata']['created_at'] ?? now()->toDateTimeString(),
                ],
                'cleaned' => [
                    'deleted' => $cleanResult['deleted'] ?? 0,
                    'kept' => $keep,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Erreur lors de la sauvegarde automatique via HTTP: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la sauvegarde: ' . $e->getMessage(),
            ], 500);
        }
    }
}
