<?php

namespace App\Services\Audit;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PerformanceChecker extends BaseChecker
{
    public function key(): string
    {
        return 'performance';
    }

    public function label(): string
    {
        return 'Performance & Efficacité';
    }

    public function run(): array
    {
        $items = [];
        $recommendations = [];
        $score = 100;

        // Taille de la base de données
        $dbPath = database_path('database.sqlite');
        $dbSize = file_exists($dbPath) ? filesize($dbPath) : 0;
        $dbSizeMb = round($dbSize / 1024 / 1024, 2);
        $items[] = ['label' => 'Taille BDD', 'value' => $dbSizeMb . ' MB', 'severity' => $dbSizeMb > 500 ? 'critical' : ($dbSizeMb > 200 ? 'warning' : 'ok')];
        if ($dbSizeMb > 500) {
            $score -= 10;
            $recommendations[] = 'La base de données est volumineuse — envisager un archivage des anciennes données.';
        }

        // Tables les plus volumineuses
        $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        $tableSizes = [];
        foreach ($tables as $table) {
            $count = DB::table($table->name)->count();
            $tableSizes[$table->name] = $count;
        }
        arsort($tableSizes);
        $topTables = array_slice($tableSizes, 0, 5, true);
        foreach ($topTables as $name => $count) {
            $items[] = ['label' => "Table: {$name}", 'value' => number_format($count) . ' lignes', 'severity' => $count > 100000 ? 'warning' : 'ok'];
        }

        // Vérifier les migrations en attente
        try {
            $pendingMigrations = $this->getPendingMigrationsCount();
            $items[] = ['label' => 'Migrations en attente', 'value' => $pendingMigrations, 'severity' => $pendingMigrations > 0 ? 'warning' : 'ok'];
            if ($pendingMigrations > 0) {
                $score -= 10;
                $recommendations[] = "Il y a {$pendingMigrations} migration(s) en attente — exécuter php artisan migrate.";
            }
        } catch (\Exception $e) {
            $items[] = ['label' => 'Migrations en attente', 'value' => 'Erreur de vérification', 'severity' => 'warning'];
        }

        // Cache driver
        $cacheDriver = config('cache.default');
        $items[] = ['label' => 'Driver de cache', 'value' => $cacheDriver, 'severity' => in_array($cacheDriver, ['redis', 'memcached']) ? 'ok' : 'warning'];
        if (!in_array($cacheDriver, ['redis', 'memcached'])) {
            $recommendations[] = "Le cache utilise '{$cacheDriver}' — Redis ou Memcached serait plus performant en production.";
        }

        // Session driver
        $sessionDriver = config('session.driver');
        $items[] = ['label' => 'Driver de session', 'value' => $sessionDriver, 'severity' => 'info'];

        // Queue driver
        $queueDriver = config('queue.default');
        $items[] = ['label' => 'Driver de queue', 'value' => $queueDriver, 'severity' => $queueDriver === 'sync' ? 'warning' : 'ok'];
        if ($queueDriver === 'sync') {
            $score -= 10;
            $recommendations[] = 'La queue est en mode synchrone — configurer un driver async (database, redis).';
        }

        // Config/route cache
        $configCached = file_exists(base_path('bootstrap/cache/config.php'));
        $routesCached = file_exists(base_path('bootstrap/cache/routes-v7.php'));
        $items[] = ['label' => 'Cache de config', 'value' => $configCached ? 'Actif' : 'Inactif', 'severity' => $configCached ? 'ok' : 'warning'];
        $items[] = ['label' => 'Cache de routes', 'value' => $routesCached ? 'Actif' : 'Inactif', 'severity' => $routesCached ? 'ok' : 'warning'];

        if (!$configCached && app()->environment('production')) {
            $score -= 5;
            $recommendations[] = 'Le cache de config n\'est pas actif — exécuter php artisan config:cache.';
        }
        if (!$routesCached && app()->environment('production')) {
            $score -= 5;
            $recommendations[] = 'Le cache de routes n\'est pas actif — exécuter php artisan route:cache.';
        }

        // OPcache
        $opcacheEnabled = function_exists('opcache_get_status') && opcache_get_status() !== false;
        $items[] = ['label' => 'OPcache', 'value' => $opcacheEnabled ? 'Actif' : 'Inactif', 'severity' => $opcacheEnabled ? 'ok' : 'warning'];
        if (!$opcacheEnabled) {
            $recommendations[] = 'OPcache n\'est pas activé — gain de performance significatif.';
        }

        return $this->result($score, $items, $recommendations);
    }

    private function getPendingMigrationsCount(): int
    {
        $migrationFiles = glob(database_path('migrations/*.php'));
        $ranMigrations = DB::table('migrations')->pluck('migration')->toArray();

        $pending = 0;
        foreach ($migrationFiles as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            if (!in_array($name, $ranMigrations)) {
                $pending++;
            }
        }

        return $pending;
    }
}
