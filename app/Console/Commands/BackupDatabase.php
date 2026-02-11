<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\Log;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {--keep=10 : Nombre de sauvegardes à conserver}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Créer une sauvegarde automatique de la base de données';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseBackupService $backupService)
    {
        $this->info('Création de la sauvegarde de la base de données...');

        try {
            $result = $backupService->createBackup('Sauvegarde automatique');
            
            $this->info("✓ Sauvegarde créée avec succès: {$result['filename']}");
            $this->info("  Taille: " . $this->formatBytes($result['size']));
            
            // Nettoyer les anciennes sauvegardes
            $keep = (int) $this->option('keep');
            $cleanResult = $backupService->cleanOldBackups($keep);
            
            if ($cleanResult['deleted'] > 0) {
                $this->info("✓ {$cleanResult['deleted']} ancienne(s) sauvegarde(s) supprimée(s)");
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("✗ Erreur lors de la création de la sauvegarde: " . $e->getMessage());
            Log::error("Erreur lors de la sauvegarde automatique: " . $e->getMessage());
            
            return Command::FAILURE;
        }
    }

    /**
     * Formater les octets en format lisible
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
