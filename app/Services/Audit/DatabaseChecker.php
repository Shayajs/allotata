<?php

namespace App\Services\Audit;

use Illuminate\Support\Facades\DB;

class DatabaseChecker extends BaseChecker
{
    public function key(): string
    {
        return 'database';
    }

    public function label(): string
    {
        return 'Base de données';
    }

    public function run(): array
    {
        $items = [];
        $recommendations = [];
        $score = 100;

        // Taille BDD
        $dbPath = database_path('database.sqlite');
        $dbSize = file_exists($dbPath) ? filesize($dbPath) : 0;
        $dbSizeMb = round($dbSize / 1024 / 1024, 2);
        $items[] = ['label' => 'Taille fichier SQLite', 'value' => $dbSizeMb . ' MB', 'severity' => $dbSizeMb > 500 ? 'critical' : ($dbSizeMb > 200 ? 'warning' : 'ok')];

        // Nombre de tables
        $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        $items[] = ['label' => 'Nombre de tables', 'value' => count($tables), 'severity' => 'info'];

        // Intégrité de la BDD
        try {
            $integrity = DB::select("PRAGMA integrity_check");
            $isOk = isset($integrity[0]) && $integrity[0]->integrity_check === 'ok';
            $items[] = ['label' => 'Intégrité BDD', 'value' => $isOk ? 'OK' : 'ERREUR', 'severity' => $isOk ? 'ok' : 'critical'];
            if (!$isOk) {
                $score -= 30;
                $recommendations[] = 'L\'intégrité de la base de données est compromise — action urgente requise.';
            }
        } catch (\Exception $e) {
            $items[] = ['label' => 'Intégrité BDD', 'value' => 'Erreur de vérification', 'severity' => 'warning'];
        }

        // Tables sans index (en dehors de la PK)
        $tablesWithoutIndexes = [];
        foreach ($tables as $table) {
            $indexes = DB::select("PRAGMA index_list('{$table->name}')");
            $count = DB::table($table->name)->count();
            if (empty($indexes) && $count > 1000) {
                $tablesWithoutIndexes[] = $table->name . " ({$count} lignes)";
            }
        }

        if (!empty($tablesWithoutIndexes)) {
            $items[] = ['label' => 'Tables volumineuses sans index', 'value' => implode(', ', array_slice($tablesWithoutIndexes, 0, 5)), 'severity' => 'warning'];
            $score -= min(15, count($tablesWithoutIndexes) * 3);
            $recommendations[] = 'Certaines tables volumineuses n\'ont pas d\'index — améliorer les performances de requête.';
        } else {
            $items[] = ['label' => 'Tables volumineuses sans index', 'value' => 'Aucune', 'severity' => 'ok'];
        }

        // Migrations en attente
        try {
            $migrationFiles = glob(database_path('migrations/*.php'));
            $ranMigrations = DB::table('migrations')->pluck('migration')->toArray();
            $pending = 0;
            foreach ($migrationFiles as $file) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                if (!in_array($name, $ranMigrations)) {
                    $pending++;
                }
            }
            $items[] = ['label' => 'Migrations en attente', 'value' => $pending, 'severity' => $pending > 0 ? 'warning' : 'ok'];
            if ($pending > 0) {
                $score -= 10;
                $recommendations[] = "{$pending} migration(s) en attente.";
            }
        } catch (\Exception $e) {
            $items[] = ['label' => 'Migrations', 'value' => 'Erreur vérification', 'severity' => 'warning'];
        }

        // Foreign keys activées
        try {
            $fk = DB::select("PRAGMA foreign_keys");
            $fkEnabled = isset($fk[0]) && $fk[0]->foreign_keys == 1;
            $items[] = ['label' => 'Foreign keys', 'value' => $fkEnabled ? 'Activées' : 'Désactivées', 'severity' => $fkEnabled ? 'ok' : 'warning'];
            if (!$fkEnabled) {
                $recommendations[] = 'Les clés étrangères ne sont pas activées — risque d\'incohérence des données.';
            }
        } catch (\Exception $e) {
            // Ignorer silencieusement
        }

        // WAL mode
        try {
            $journal = DB::select("PRAGMA journal_mode");
            $journalMode = $journal[0]->journal_mode ?? 'unknown';
            $items[] = ['label' => 'Mode journal', 'value' => strtoupper($journalMode), 'severity' => $journalMode === 'wal' ? 'ok' : 'warning'];
            if ($journalMode !== 'wal') {
                $recommendations[] = 'Activer le mode WAL pour de meilleures performances concurrentes.';
            }
        } catch (\Exception $e) {
            // Ignorer silencieusement
        }

        // Jobs en échec
        $failedJobs = DB::table('failed_jobs')->count();
        $items[] = ['label' => 'Jobs en échec (failed_jobs)', 'value' => $failedJobs, 'severity' => $failedJobs > 10 ? 'critical' : ($failedJobs > 3 ? 'warning' : 'ok')];
        if ($failedJobs > 10) {
            $score -= 10;
            $recommendations[] = "{$failedJobs} job(s) en échec dans la table failed_jobs.";
        }

        return $this->result($score, $items, $recommendations);
    }
}
