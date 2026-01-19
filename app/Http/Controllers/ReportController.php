<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Exporter les réservations en CSV
     */
    public function exportReservations(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $query = Reservation::where('entreprise_id', $entreprise->id)
            ->with(['user', 'typeService']);

        // Filtres
        if ($request->filled('date_debut')) {
            $query->whereDate('date_reservation', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_reservation', '<=', $request->date_fin);
        }
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $reservations = $query->orderBy('date_reservation', 'desc')->get();

        $filename = "reservations_{$entreprise->slug}_" . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($reservations) {
            $file = fopen('php://output', 'w');
            
            // En-têtes
            fputcsv($file, [
                'ID',
                'Date',
                'Heure',
                'Client',
                'Email',
                'Téléphone',
                'Service',
                'Prix',
                'Statut',
                'Payé',
                'Lieu',
                'Notes'
            ]);

            // Données
            foreach ($reservations as $reservation) {
                fputcsv($file, [
                    $reservation->id,
                    $reservation->date_reservation ? $reservation->date_reservation->format('Y-m-d') : '',
                    $reservation->date_reservation ? $reservation->date_reservation->format('H:i') : '',
                    $reservation->user ? $reservation->user->name : ($reservation->nom_client ?? ''),
                    $reservation->user ? $reservation->user->email : ($reservation->email_client ?? ''),
                    $reservation->telephone_client ?? '',
                    $reservation->type_service ?? '',
                    number_format($reservation->prix, 2, ',', ' '),
                    $reservation->statut,
                    $reservation->est_paye ? 'Oui' : 'Non',
                    $reservation->lieu ?? '',
                    $reservation->notes ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Exporter un rapport financier en PDF
     */
    public function exportFinancialReport(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $dateDebut = $request->filled('date_debut') ? $request->date_debut : now()->startOfMonth()->format('Y-m-d');
        $dateFin = $request->filled('date_fin') ? $request->date_fin : now()->endOfMonth()->format('Y-m-d');

        $reservations = Reservation::where('entreprise_id', $entreprise->id)
            ->whereBetween(DB::raw('DATE(date_reservation)'), [$dateDebut, $dateFin])
            ->with(['user', 'typeService'])
            ->get();

        $reservationsAcceptees = $reservations->filter(function($r) {
            return in_array($r->statut, ['confirmee', 'terminee']);
        });

        $stats = [
            'total_reservations' => $reservations->count(),
            'reservations_confirmees' => $reservations->where('statut', 'confirmee')->count(),
            'reservations_terminees' => $reservations->where('statut', 'terminee')->count(),
            'revenu_total' => $reservationsAcceptees->sum('prix'),
            'revenu_paye' => $reservations->where('est_paye', true)->sum('prix'),
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
        ];

        // Générer le PDF avec une vue Blade
        $html = view('reports.financial-pdf', [
            'entreprise' => $entreprise,
            'stats' => $stats,
            'reservations' => $reservations,
        ])->render();

        // Utiliser DomPDF ou une alternative simple
        // Pour l'instant, on retourne une vue HTML que l'utilisateur peut imprimer
        return view('reports.financial-pdf', [
            'entreprise' => $entreprise,
            'stats' => $stats,
            'reservations' => $reservations,
        ]);
    }
}
