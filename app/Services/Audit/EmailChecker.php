<?php

namespace App\Services\Audit;

use App\Models\EmailLog;

class EmailChecker extends BaseChecker
{
    public function key(): string
    {
        return 'emails';
    }

    public function label(): string
    {
        return 'Emails';
    }

    public function run(): array
    {
        $items = [];
        $recommendations = [];
        $score = 100;

        $period = now()->subDays(30);

        // Totaux
        $totalEmails = EmailLog::where('created_at', '>=', $period)->count();
        $sentEmails = EmailLog::where('created_at', '>=', $period)->where('status', 'sent')->count();
        $failedEmails = EmailLog::where('created_at', '>=', $period)->where('status', 'failed')->count();
        $failRate = $totalEmails > 0 ? round(($failedEmails / $totalEmails) * 100, 1) : 0;

        $items[] = ['label' => 'Emails envoyés (30j)', 'value' => $sentEmails, 'severity' => 'info'];
        $items[] = ['label' => 'Emails échoués (30j)', 'value' => $failedEmails, 'severity' => $failedEmails > 10 ? 'critical' : ($failedEmails > 3 ? 'warning' : 'ok')];
        $items[] = ['label' => 'Taux d\'échec', 'value' => $failRate . '%', 'severity' => $failRate > 10 ? 'critical' : ($failRate > 5 ? 'warning' : 'ok')];

        $score -= min(25, $failedEmails * 2);

        // Échecs récents (24h)
        $recentFails = EmailLog::where('created_at', '>=', now()->subDay())->where('status', 'failed')->count();
        $items[] = ['label' => 'Échecs dernières 24h', 'value' => $recentFails, 'severity' => $recentFails > 5 ? 'critical' : ($recentFails > 0 ? 'warning' : 'ok')];
        if ($recentFails > 5) {
            $score -= 15;
            $recommendations[] = 'Nombreux échecs d\'envoi dans les dernières 24h — vérifier la configuration SMTP.';
        }

        // Types d'erreurs les plus fréquentes
        $errorTypes = EmailLog::where('created_at', '>=', $period)
            ->where('status', 'failed')
            ->whereNotNull('error_message')
            ->selectRaw('error_message, COUNT(*) as count')
            ->groupBy('error_message')
            ->orderByDesc('count')
            ->limit(3)
            ->get();

        foreach ($errorTypes as $error) {
            $items[] = ['label' => 'Erreur fréquente', 'value' => \Str::limit($error->error_message, 60) . " ({$error->count}x)", 'severity' => 'info'];
        }

        // Configuration mail
        $mailDriver = config('mail.default');
        $mailHost = config("mail.mailers.{$mailDriver}.host", '');
        $items[] = ['label' => 'Driver mail', 'value' => $mailDriver, 'severity' => 'info'];
        $items[] = ['label' => 'Hôte SMTP', 'value' => $mailHost ?: 'Non configuré', 'severity' => !empty($mailHost) ? 'ok' : 'warning'];

        if (empty($mailHost) && $mailDriver === 'smtp') {
            $score -= 15;
            $recommendations[] = 'Aucun hôte SMTP configuré — les emails ne seront pas envoyés.';
        }

        if ($failRate > 5) {
            $recommendations[] = "Le taux d'échec d'emails est de {$failRate}% — investiguer les causes.";
        }

        return $this->result($score, $items, $recommendations);
    }
}
