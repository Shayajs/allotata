<?php

namespace App\Services\Audit;

use App\Models\SmsLog;

class SmsChecker extends BaseChecker
{
    public function key(): string
    {
        return 'sms';
    }

    public function label(): string
    {
        return 'SMS';
    }

    public function run(): array
    {
        $items = [];
        $recommendations = [];
        $score = 100;

        $period = now()->subDays(30);

        $totalSms = SmsLog::where('created_at', '>=', $period)->count();
        $sentSms = SmsLog::where('created_at', '>=', $period)->where('statut', 'envoye')->count();
        $failedSms = SmsLog::where('created_at', '>=', $period)->where('statut', 'echec')->count();
        $failRate = $totalSms > 0 ? round(($failedSms / $totalSms) * 100, 1) : 0;

        $items[] = ['label' => 'SMS envoyés (30j)', 'value' => $sentSms, 'severity' => 'info'];
        $items[] = ['label' => 'SMS échoués (30j)', 'value' => $failedSms, 'severity' => $failedSms > 5 ? 'critical' : ($failedSms > 2 ? 'warning' : 'ok')];
        $items[] = ['label' => 'Taux d\'échec', 'value' => $failRate . '%', 'severity' => $failRate > 10 ? 'critical' : ($failRate > 5 ? 'warning' : 'ok')];
        $items[] = ['label' => 'Total SMS (30j)', 'value' => $totalSms, 'severity' => 'info'];

        $score -= min(30, $failedSms * 5);

        // Échecs récents
        $recentFails = SmsLog::where('created_at', '>=', now()->subDay())->where('statut', 'echec')->count();
        $items[] = ['label' => 'Échecs dernières 24h', 'value' => $recentFails, 'severity' => $recentFails > 3 ? 'critical' : ($recentFails > 0 ? 'warning' : 'ok')];

        if ($recentFails > 3) {
            $score -= 15;
            $recommendations[] = 'Plusieurs SMS en échec récemment — vérifier le provider Twilio.';
        }

        // Provider configuré
        $twilioSid = config('services.twilio.sid', env('TWILIO_SID'));
        $items[] = ['label' => 'Twilio configuré', 'value' => !empty($twilioSid) ? 'Oui' : 'Non', 'severity' => !empty($twilioSid) ? 'ok' : 'warning'];
        if (empty($twilioSid)) {
            $score -= 10;
            $recommendations[] = 'Twilio n\'est pas configuré — les SMS ne seront pas envoyés.';
        }

        // Erreurs fréquentes
        $errorMessages = SmsLog::where('created_at', '>=', $period)
            ->where('statut', 'echec')
            ->whereNotNull('error_message')
            ->selectRaw('error_message, COUNT(*) as count')
            ->groupBy('error_message')
            ->orderByDesc('count')
            ->limit(3)
            ->get();

        foreach ($errorMessages as $error) {
            $items[] = ['label' => 'Erreur SMS', 'value' => \Str::limit($error->error_message, 60) . " ({$error->count}x)", 'severity' => 'info'];
        }

        if ($failRate > 10) {
            $recommendations[] = "Taux d'échec SMS élevé ({$failRate}%) — vérifier les numéros destinataires.";
        }

        return $this->result($score, $items, $recommendations);
    }
}
