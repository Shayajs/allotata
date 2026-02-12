<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Afficher la page d'accueil avec mini dashboard si connecté
     */
    public function index()
    {
        $user = Auth::user();
        $miniStats = null;

        if ($user) {
            // Statistiques pour les clients
            $clientStats = null;
            if ($user->est_client) {
                $reservations = $user->reservations()
                    ->with('entreprise')
                    ->get();
                
                $clientStats = [
                    'total_reservations' => $reservations->count(),
                    'reservations_en_attente' => $reservations->where('statut', 'en_attente')->count(),
                    'reservations_confirmees' => $reservations->where('statut', 'confirmee')->count(),
                    'reservations_terminees' => $reservations->where('statut', 'terminee')->count(),
                    'total_depense' => $reservations->sum('prix'),
                    'reservations_ce_mois' => $reservations->filter(function($r) {
                        return $r->date_reservation->isCurrentMonth();
                    })->count(),
                ];
            }

            // Statistiques pour les gérants
            $gerantStats = null;
            if ($user->est_gerant) {
                $entreprises = $user->entreprises()->withCount('reservations')->get();
                
                if ($entreprises->count() > 0) {
                    $allReservations = Reservation::whereIn('entreprise_id', $entreprises->pluck('id'))->get();
                    // Réservations acceptées uniquement (confirmées ou terminées)
                    $reservationsAcceptees = $allReservations->filter(function($r) {
                        return in_array($r->statut, ['confirmee', 'terminee']);
                    });
                    
                    $gerantStats = [
                        'total_entreprises' => $entreprises->count(),
                        'total_reservations' => $allReservations->count(),
                        'revenu_total' => $reservationsAcceptees->sum('prix'), // Uniquement les réservations acceptées
                        'revenu_paye' => $allReservations->where('est_paye', true)->sum('prix'), // CA : paiements confirmés
                        'reservations_ce_mois' => $allReservations->filter(function($r) {
                            return $r->date_reservation->isCurrentMonth();
                        })->count(),
                        'revenu_ce_mois' => $reservationsAcceptees->filter(function($r) {
                            return $r->date_reservation->isCurrentMonth();
                        })->sum('prix'),
                    ];
                }
            }

            $miniStats = [
                'client' => $clientStats,
                'gerant' => $gerantStats,
            ];
        }

        return view('welcome', [
            'user' => $user,
            'miniStats' => $miniStats,
        ]);
    }
}
