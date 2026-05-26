<?php

namespace App\Services\Audit;

use App\Models\User;
use App\Models\Entreprise;
use App\Models\Reservation;
use App\Models\UserPresence;

class UserActivityChecker extends BaseChecker
{
    public function key(): string
    {
        return 'users';
    }

    public function label(): string
    {
        return 'Utilisateurs & Activité';
    }

    public function run(): array
    {
        $items = [];
        $recommendations = [];
        $score = 100;

        // Utilisateurs totaux
        $totalUsers = User::count();
        $activeUsers30d = User::whereHas('presence', fn ($q) => $q->where('last_activity_at', '>=', now()->subDays(30)))->count();
        $activeUsers7d = User::whereHas('presence', fn ($q) => $q->where('last_activity_at', '>=', now()->subDays(7)))->count();
        $newUsers30d = User::where('created_at', '>=', now()->subDays(30))->count();

        $items[] = ['label' => 'Utilisateurs totaux', 'value' => $totalUsers, 'severity' => 'info'];
        $items[] = ['label' => 'Actifs (30j)', 'value' => $activeUsers30d, 'severity' => 'info'];
        $items[] = ['label' => 'Actifs (7j)', 'value' => $activeUsers7d, 'severity' => 'info'];
        $items[] = ['label' => 'Nouveaux inscrits (30j)', 'value' => $newUsers30d, 'severity' => 'info'];

        // Taux d'engagement
        $engagementRate = $totalUsers > 0 ? round(($activeUsers30d / $totalUsers) * 100) : 0;
        $items[] = ['label' => 'Taux d\'engagement (30j)', 'value' => $engagementRate . '%', 'severity' => $engagementRate >= 30 ? 'ok' : ($engagementRate >= 15 ? 'warning' : 'critical')];
        if ($engagementRate < 15) {
            $score -= 10;
            $recommendations[] = 'Le taux d\'engagement est bas — envisager des campagnes de rétention.';
        }

        // Entreprises
        $totalEntreprises = Entreprise::count();
        $verifiedEntreprises = Entreprise::where('est_verifiee', true)->count();
        $pendingVerification = Entreprise::where('est_verifiee', false)->count();

        $items[] = ['label' => 'Entreprises totales', 'value' => $totalEntreprises, 'severity' => 'info'];
        $items[] = ['label' => 'Entreprises vérifiées', 'value' => $verifiedEntreprises, 'severity' => 'info'];
        $items[] = ['label' => 'En attente de vérification', 'value' => $pendingVerification, 'severity' => $pendingVerification > 10 ? 'warning' : 'ok'];
        if ($pendingVerification > 10) {
            $score -= 5;
            $recommendations[] = "{$pendingVerification} entreprises attendent une vérification.";
        }

        // Réservations
        $reservations30d = Reservation::where('created_at', '>=', now()->subDays(30))->count();
        $reservations7d = Reservation::where('created_at', '>=', now()->subDays(7))->count();
        $items[] = ['label' => 'Réservations (30j)', 'value' => $reservations30d, 'severity' => 'info'];
        $items[] = ['label' => 'Réservations (7j)', 'value' => $reservations7d, 'severity' => 'info'];

        // Comptes en anomalie
        $limitedAccounts = User::where('statut_compte', 'limite')->count();
        $bannedAccounts = User::where('statut_compte', 'interdit')->count();
        $items[] = ['label' => 'Comptes limités', 'value' => $limitedAccounts, 'severity' => $limitedAccounts > 5 ? 'warning' : 'ok'];
        $items[] = ['label' => 'Comptes bannis', 'value' => $bannedAccounts, 'severity' => 'info'];

        // Emails non vérifiés
        $unverifiedEmails = User::whereNull('email_verified_at')
            ->where('created_at', '<', now()->subDays(3))
            ->count();
        $items[] = ['label' => 'Emails non vérifiés (>3j)', 'value' => $unverifiedEmails, 'severity' => $unverifiedEmails > 20 ? 'warning' : 'ok'];

        return $this->result($score, $items, $recommendations);
    }
}
