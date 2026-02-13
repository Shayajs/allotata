<?php

namespace App\Console\Commands;

use App\Models\GdprRequest;
use App\Services\GdprService;
use Illuminate\Console\Command;

class ProcessGdprRequests extends Command
{
    protected $signature = 'gdpr:process-requests';
    protected $description = 'Traite les demandes RGPD en attente (suppressions dont le délai de grâce est écoulé + nettoyage des exports expirés)';

    public function handle(GdprService $gdprService): int
    {
        $this->info('=== Traitement des demandes RGPD ===');

        // 1. Exécuter les suppressions dont le délai est écoulé
        $pendingDeletions = GdprRequest::readyForExecution()->with('user')->get();

        $this->info("Suppressions en attente d'exécution : {$pendingDeletions->count()}");

        $successCount = 0;
        $failCount = 0;

        foreach ($pendingDeletions as $request) {
            $userName = $request->user?->name ?? "ID:{$request->user_id}";
            $this->line("  Traitement : {$userName} (demande #{$request->id})...");

            $success = $gdprService->executeDeletion($request);

            if ($success) {
                $this->info("    -> Anonymisation effectuée.");
                $successCount++;
            } else {
                $this->error("    -> ÉCHEC. Consultez les logs.");
                $failCount++;
            }
        }

        if ($pendingDeletions->count() > 0) {
            $this->info("Résultat : {$successCount} réussie(s), {$failCount} échouée(s).");
        }

        // 2. Nettoyer les exports expirés du disque
        $cleanedCount = $gdprService->cleanupExpiredExports();
        if ($cleanedCount > 0) {
            $this->info("Exports expirés nettoyés : {$cleanedCount}");
        }

        $this->info('=== Terminé ===');

        return $failCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
