<?php

namespace App\Console\Commands;

use App\Jobs\RunSiteAudit;
use App\Models\SiteAudit;
use App\Models\User;
use Illuminate\Console\Command;

class SiteAuditCommand extends Command
{
    protected $signature = 'site:audit
        {--sync : Exécuter en synchrone (sans queue)}
        {--user= : ID de l\'utilisateur qui lance l\'audit}';

    protected $description = 'Lancer un audit complet du site (sécurité, stripe, erreurs, performance, etc.)';

    public function handle(): int
    {
        $userId = $this->option('user') ?? User::where('is_admin', true)->first()?->id;

        if (!$userId) {
            $this->error('Aucun utilisateur admin trouvé. Utilisez --user=ID.');
            return self::FAILURE;
        }

        $audit = SiteAudit::create([
            'user_id' => $userId,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $this->info("Audit #{$audit->id} créé pour l'utilisateur #{$userId}.");

        if ($this->option('sync')) {
            $this->info('Exécution en mode synchrone...');
            $this->newLine();

            $job = new RunSiteAudit($audit->id);
            $job->handle();

            $audit->refresh();
            $this->displayResults($audit);
        } else {
            RunSiteAudit::dispatch($audit->id);
            $this->info('L\'audit a été mis en file d\'attente.');
            $this->info('Vous pouvez quitter — une notification sera envoyée à la fin.');
            $this->info("Suivi : /admin/audits/{$audit->id}");
        }

        return self::SUCCESS;
    }

    private function displayResults(SiteAudit $audit): void
    {
        if ($audit->status === 'failed') {
            $this->error('L\'audit a échoué.');
            return;
        }

        $this->info("Note globale : {$audit->note_globale}/100");
        $this->info("Durée : {$audit->duration_formatted}");
        $this->newLine();

        $resume = $audit->resume ?? [];
        $rows = [];

        foreach ($resume as $key => $data) {
            $statusIcon = match ($data['status']) {
                'ok' => '<fg=green>●</>',
                'warning' => '<fg=yellow>●</>',
                'critical' => '<fg=red>●</>',
                default => '<fg=gray>●</>',
            };

            $rows[] = [
                $statusIcon,
                $data['label'],
                $data['score'] . '/100',
                $data['recommendations_count'] > 0 ? $data['recommendations_count'] . ' recommandation(s)' : '-',
            ];
        }

        $this->table(['', 'Catégorie', 'Score', 'Recommandations'], $rows);

        // Recommandations globales
        $resultats = $audit->resultats ?? [];
        $allRecommendations = [];
        foreach ($resultats as $result) {
            foreach ($result['recommendations'] ?? [] as $rec) {
                $allRecommendations[] = [$result['label'], $rec];
            }
        }

        if (!empty($allRecommendations)) {
            $this->newLine();
            $this->warn('Recommandations :');
            foreach ($allRecommendations as [$category, $rec]) {
                $this->line("  [{$category}] {$rec}");
            }
        }
    }
}
