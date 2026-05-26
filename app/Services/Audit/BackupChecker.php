<?php

namespace App\Services\Audit;

use App\Models\DatabaseBackup;

class BackupChecker extends BaseChecker
{
    public function key(): string
    {
        return 'backups';
    }

    public function label(): string
    {
        return 'Sauvegardes';
    }

    public function run(): array
    {
        $items = [];
        $recommendations = [];
        $score = 100;

        // Dernière sauvegarde
        $lastBackup = DatabaseBackup::orderByDesc('created_at')->first();
        $lastBackupAge = $lastBackup ? now()->diffInHours($lastBackup->created_at) : null;

        $items[] = [
            'label' => 'Dernière sauvegarde',
            'value' => $lastBackup ? $lastBackup->created_at->format('d/m/Y H:i') : 'Aucune',
            'severity' => $lastBackupAge === null ? 'critical' : ($lastBackupAge > 48 ? 'critical' : ($lastBackupAge > 24 ? 'warning' : 'ok')),
        ];

        if ($lastBackupAge === null) {
            $score -= 40;
            $recommendations[] = 'Aucune sauvegarde trouvée — configurer les sauvegardes automatiques.';
        } elseif ($lastBackupAge > 48) {
            $score -= 30;
            $recommendations[] = 'La dernière sauvegarde date de plus de 48h — vérifier la tâche planifiée.';
        } elseif ($lastBackupAge > 24) {
            $score -= 10;
        }

        // Taille dernière sauvegarde
        if ($lastBackup) {
            $items[] = ['label' => 'Taille dernière sauvegarde', 'value' => $lastBackup->formatted_size, 'severity' => 'info'];

            // Vérifier que le fichier existe toujours
            $fileExists = $lastBackup->fileExists();
            $items[] = ['label' => 'Fichier accessible', 'value' => $fileExists ? 'Oui' : 'Non', 'severity' => $fileExists ? 'ok' : 'critical'];
            if (!$fileExists) {
                $score -= 20;
                $recommendations[] = 'Le fichier de la dernière sauvegarde est introuvable.';
            }
        }

        // Nombre de sauvegardes disponibles
        $backupCount = DatabaseBackup::count();
        $items[] = ['label' => 'Sauvegardes disponibles', 'value' => $backupCount, 'severity' => $backupCount >= 7 ? 'ok' : ($backupCount >= 3 ? 'warning' : 'critical')];
        if ($backupCount < 3) {
            $score -= 10;
            $recommendations[] = 'Moins de 3 sauvegardes disponibles — augmenter la rétention.';
        }

        // Régularité (sauvegardes des 7 derniers jours)
        $recentBackups = DatabaseBackup::where('created_at', '>=', now()->subDays(7))->count();
        $items[] = ['label' => 'Sauvegardes (7 derniers jours)', 'value' => $recentBackups, 'severity' => $recentBackups >= 5 ? 'ok' : ($recentBackups >= 3 ? 'warning' : 'critical')];
        if ($recentBackups < 5) {
            $score -= 10;
            $recommendations[] = 'Les sauvegardes ne sont pas quotidiennes — vérifier le cron.';
        }

        // Intégrité : tailles cohérentes
        $lastBackups = DatabaseBackup::orderByDesc('created_at')->limit(5)->get();
        if ($lastBackups->count() >= 2) {
            $sizes = $lastBackups->pluck('size')->filter();
            if ($sizes->count() >= 2) {
                $avgSize = $sizes->avg();
                $minSize = $sizes->min();
                if ($avgSize > 0 && $minSize < $avgSize * 0.5) {
                    $items[] = ['label' => 'Cohérence des tailles', 'value' => 'Incohérent', 'severity' => 'warning'];
                    $score -= 10;
                    $recommendations[] = 'Certaines sauvegardes sont anormalement petites — vérifier leur intégrité.';
                } else {
                    $items[] = ['label' => 'Cohérence des tailles', 'value' => 'OK', 'severity' => 'ok'];
                }
            }
        }

        return $this->result($score, $items, $recommendations);
    }
}
