<?php

namespace App\Services;

use App\Models\User;
use App\Models\Entreprise;
use App\Models\Reservation;
use App\Helpers\EmailHelper;
use Illuminate\Support\Facades\Log;

class EmailReportService
{
    /**
     * Génère et envoie un rapport hebdomadaire pour une entreprise
     */
    public function sendWeeklyReport(Entreprise $entreprise): bool
    {
        $user = $entreprise->user;
        if (!$user || !$user->email) {
            return false;
        }

        // Calculer les statistiques de la semaine
        $debutSemaine = now()->startOfWeek();
        $finSemaine = now()->endOfWeek();

        $reservations = Reservation::where('entreprise_id', $entreprise->id)
            ->whereBetween('date_reservation', [$debutSemaine, $finSemaine])
            ->get();

        $reservationsAcceptees = $reservations->filter(function($r) {
            return in_array($r->statut, ['confirmee', 'terminee']);
        });

        $stats = [
            'total_reservations' => $reservations->count(),
            'reservations_confirmees' => $reservations->where('statut', 'confirmee')->count(),
            'reservations_en_attente' => $reservations->where('statut', 'en_attente')->count(),
            'revenu_total' => $reservationsAcceptees->sum('prix'),
            'revenu_paye' => $reservations->where('est_paye', true)->sum('prix'),
        ];

        try {
            EmailHelper::sendWeeklyReport($user, $entreprise, $stats);
            return true;
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi du rapport hebdomadaire pour l'entreprise #{$entreprise->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Génère et envoie un rapport mensuel pour une entreprise
     */
    public function sendMonthlyReport(Entreprise $entreprise): bool
    {
        $user = $entreprise->user;
        if (!$user || !$user->email) {
            return false;
        }

        // Calculer les statistiques du mois
        $debutMois = now()->startOfMonth();
        $finMois = now()->endOfMonth();

        $reservations = Reservation::where('entreprise_id', $entreprise->id)
            ->whereBetween('date_reservation', [$debutMois, $finMois])
            ->get();

        $reservationsAcceptees = $reservations->filter(function($r) {
            return in_array($r->statut, ['confirmee', 'terminee']);
        });

        $stats = [
            'total_reservations' => $reservations->count(),
            'reservations_confirmees' => $reservations->where('statut', 'confirmee')->count(),
            'reservations_en_attente' => $reservations->where('statut', 'en_attente')->count(),
            'reservations_terminees' => $reservations->where('statut', 'terminee')->count(),
            'revenu_total' => $reservationsAcceptees->sum('prix'),
            'revenu_paye' => $reservations->where('est_paye', true)->sum('prix'),
        ];

        try {
            // Utiliser le même template que le rapport hebdomadaire (ou créer un template mensuel si nécessaire)
            EmailHelper::sendWeeklyReport($user, $entreprise, $stats);
            return true;
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi du rapport mensuel pour l'entreprise #{$entreprise->id}: " . $e->getMessage());
            return false;
        }
    }
}
