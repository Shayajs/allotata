<?php

namespace App\Services\Audit;

use App\Models\GdprRequest;
use App\Models\User;

class GdprChecker extends BaseChecker
{
    public function key(): string
    {
        return 'gdpr';
    }

    public function label(): string
    {
        return 'RGPD & Conformité';
    }

    public function run(): array
    {
        $items = [];
        $recommendations = [];
        $score = 100;

        // Demandes RGPD en attente
        $pendingRequests = GdprRequest::where('status', 'pending')->count();
        $oldPending = GdprRequest::where('status', 'pending')
            ->where('created_at', '<', now()->subDays(30))
            ->count();

        $items[] = ['label' => 'Demandes RGPD en attente', 'value' => $pendingRequests, 'severity' => $pendingRequests > 5 ? 'critical' : ($pendingRequests > 0 ? 'warning' : 'ok')];
        $items[] = ['label' => 'En attente >30 jours', 'value' => $oldPending, 'severity' => $oldPending > 0 ? 'critical' : 'ok'];

        if ($oldPending > 0) {
            $score -= 25;
            $recommendations[] = "Des demandes RGPD dépassent le délai légal de 30 jours ({$oldPending}).";
        }
        $score -= min(15, $pendingRequests * 3);

        // Demandes traitées (30j)
        $processedRequests = GdprRequest::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        $items[] = ['label' => 'Demandes traitées (30j)', 'value' => $processedRequests, 'severity' => 'info'];

        // Comptes supprimés avec données restantes
        $deletedUsers = User::where('statut_compte', 'supprime')->count();
        $items[] = ['label' => 'Comptes marqués supprimés', 'value' => $deletedUsers, 'severity' => $deletedUsers > 20 ? 'warning' : 'ok'];

        // Consentement tracking
        $usersWithConsent = User::whereNotNull('tracking_consent')->count();
        $totalActiveUsers = User::where('statut_compte', 'normal')->count();
        $consentRate = $totalActiveUsers > 0 ? round(($usersWithConsent / $totalActiveUsers) * 100) : 0;
        $items[] = ['label' => 'Utilisateurs avec consentement enregistré', 'value' => $consentRate . '%', 'severity' => $consentRate >= 80 ? 'ok' : ($consentRate >= 50 ? 'warning' : 'critical')];

        // Politique de rétention
        $veryOldLogs = \App\Models\ErrorLog::where('created_at', '<', now()->subDays(90))->count();
        $items[] = ['label' => 'Anciens logs (>90j)', 'value' => $veryOldLogs, 'severity' => $veryOldLogs > 1000 ? 'warning' : 'ok'];
        if ($veryOldLogs > 1000) {
            $recommendations[] = 'Des logs de plus de 90 jours existent encore — mettre en place une politique de rétention.';
            $score -= 5;
        }

        // Vérification mentions légales
        $hasPrivacyPolicy = file_exists(resource_path('views/legal/privacy.blade.php'))
            || file_exists(resource_path('views/public/privacy.blade.php'))
            || file_exists(resource_path('views/pages/privacy.blade.php'));
        $items[] = ['label' => 'Politique de confidentialité', 'value' => $hasPrivacyPolicy ? 'Présente' : 'Non trouvée', 'severity' => $hasPrivacyPolicy ? 'ok' : 'warning'];
        if (!$hasPrivacyPolicy) {
            $score -= 10;
            $recommendations[] = 'Aucune page de politique de confidentialité détectée.';
        }

        return $this->result($score, $items, $recommendations);
    }
}
