<?php

namespace App\Services\Audit;

use Illuminate\Support\Facades\Storage;

class StorageChecker extends BaseChecker
{
    public function key(): string
    {
        return 'storage';
    }

    public function label(): string
    {
        return 'Stockage & Fichiers';
    }

    public function run(): array
    {
        $items = [];
        $recommendations = [];
        $score = 100;

        // Espace disque
        $storagePath = storage_path();
        $totalSpace = @disk_total_space($storagePath);
        $freeSpace = @disk_free_space($storagePath);

        if ($totalSpace && $freeSpace) {
            $usedPercent = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 1);
            $freeGb = round($freeSpace / 1024 / 1024 / 1024, 2);

            $items[] = ['label' => 'Espace disque utilisé', 'value' => $usedPercent . '%', 'severity' => $usedPercent > 90 ? 'critical' : ($usedPercent > 75 ? 'warning' : 'ok')];
            $items[] = ['label' => 'Espace libre', 'value' => $freeGb . ' GB', 'severity' => $freeGb < 1 ? 'critical' : ($freeGb < 5 ? 'warning' : 'ok')];

            if ($usedPercent > 90) {
                $score -= 25;
                $recommendations[] = 'L\'espace disque est critique (>90%) — libérer de l\'espace.';
            } elseif ($usedPercent > 75) {
                $score -= 10;
                $recommendations[] = 'L\'espace disque se remplit — surveiller.';
            }
        } else {
            $items[] = ['label' => 'Espace disque', 'value' => 'Non déterminable', 'severity' => 'warning'];
        }

        // Taille du dossier storage
        $storageSize = $this->getDirectorySize(storage_path());
        $storageSizeMb = round($storageSize / 1024 / 1024, 2);
        $items[] = ['label' => 'Taille storage/', 'value' => $storageSizeMb . ' MB', 'severity' => $storageSizeMb > 1000 ? 'warning' : 'ok'];

        // Taille des logs
        $logsPath = storage_path('logs');
        $logsSize = $this->getDirectorySize($logsPath);
        $logsSizeMb = round($logsSize / 1024 / 1024, 2);
        $items[] = ['label' => 'Taille des logs', 'value' => $logsSizeMb . ' MB', 'severity' => $logsSizeMb > 100 ? 'critical' : ($logsSizeMb > 50 ? 'warning' : 'ok')];
        if ($logsSizeMb > 100) {
            $score -= 10;
            $recommendations[] = 'Les fichiers de log sont volumineux (' . $logsSizeMb . ' MB) — configurer la rotation.';
        }

        // Vérifier les uploads
        $uploadsPath = storage_path('app/public');
        if (is_dir($uploadsPath)) {
            $uploadsSize = $this->getDirectorySize($uploadsPath);
            $uploadsSizeMb = round($uploadsSize / 1024 / 1024, 2);
            $uploadsCount = $this->countFiles($uploadsPath);
            $items[] = ['label' => 'Fichiers uploadés', 'value' => $uploadsCount . ' fichiers (' . $uploadsSizeMb . ' MB)', 'severity' => 'info'];
        }

        // Lien symbolique public/storage
        $publicStorageLink = public_path('storage');
        $symlinkExists = is_link($publicStorageLink) || is_dir($publicStorageLink);
        $items[] = ['label' => 'Lien storage public', 'value' => $symlinkExists ? 'OK' : 'Manquant', 'severity' => $symlinkExists ? 'ok' : 'warning'];
        if (!$symlinkExists) {
            $score -= 5;
            $recommendations[] = 'Le lien symbolique public/storage est manquant — php artisan storage:link.';
        }

        // Vérifier les permissions du dossier storage
        $storageWritable = is_writable(storage_path());
        $frameworkWritable = is_writable(storage_path('framework'));
        $items[] = ['label' => 'Storage accessible en écriture', 'value' => $storageWritable ? 'Oui' : 'Non', 'severity' => $storageWritable ? 'ok' : 'critical'];
        if (!$storageWritable) {
            $score -= 20;
            $recommendations[] = 'Le dossier storage n\'est pas accessible en écriture.';
        }

        // Cache framework
        $cachePath = storage_path('framework/cache');
        if (is_dir($cachePath)) {
            $cacheSize = $this->getDirectorySize($cachePath);
            $cacheSizeMb = round($cacheSize / 1024 / 1024, 2);
            $items[] = ['label' => 'Taille cache framework', 'value' => $cacheSizeMb . ' MB', 'severity' => $cacheSizeMb > 200 ? 'warning' : 'ok'];
        }

        return $this->result($score, $items, $recommendations);
    }

    private function getDirectorySize(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }

        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    private function countFiles(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }

        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $count++;
            }
        }

        return $count;
    }
}
