<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Entreprise;
use App\Models\Reservation;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ExportController extends Controller
{
    /**
     * Page d'export
     */
    public function index()
    {
        return view('admin.exports.index');
    }

    /**
     * Exporter les utilisateurs en CSV
     */
    public function exportUsers(Request $request)
    {
        $users = User::orderBy('created_at', 'desc')->get();

        $filename = 'utilisateurs_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM pour Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // En-têtes
            fputcsv($file, [
                'ID',
                'Nom',
                'Email',
                'Client',
                'Gérant',
                'Admin',
                'Abonnement actif',
                'Date inscription',
            ], ';');

            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->est_client ? 'Oui' : 'Non',
                    $user->est_gerant ? 'Oui' : 'Non',
                    $user->is_admin ? 'Oui' : 'Non',
                    $user->aAbonnementActif() ? 'Oui' : 'Non',
                    $user->created_at->format('d/m/Y H:i'),
                ], ';');
            }

            fclose($file);
        };

        ActivityLog::log('export', 'Export des utilisateurs (' . $users->count() . ' entrées)');

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Exporter les entreprises en CSV
     */
    public function exportEntreprises(Request $request)
    {
        $entreprises = Entreprise::with('user')->orderBy('created_at', 'desc')->get();

        $filename = 'entreprises_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($entreprises) {
            $file = fopen('php://output', 'w');
            
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, [
                'ID',
                'Nom',
                'Type activité',
                'SIREN',
                'Ville',
                'Email',
                'Téléphone',
                'Vérifiée',
                'Gérant',
                'Email gérant',
                'Date création',
            ], ';');

            foreach ($entreprises as $entreprise) {
                fputcsv($file, [
                    $entreprise->id,
                    $entreprise->nom,
                    $entreprise->type_activite,
                    $entreprise->siren ?? '-',
                    $entreprise->ville ?? '-',
                    $entreprise->email ?? '-',
                    $entreprise->telephone ?? '-',
                    $entreprise->est_verifiee ? 'Oui' : 'Non',
                    $entreprise->user?->name ?? '-',
                    $entreprise->user?->email ?? '-',
                    $entreprise->created_at->format('d/m/Y H:i'),
                ], ';');
            }

            fclose($file);
        };

        ActivityLog::log('export', 'Export des entreprises (' . $entreprises->count() . ' entrées)');

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Exporter les réservations en CSV
     */
    public function exportReservations(Request $request)
    {
        $query = Reservation::with(['user', 'entreprise']);

        // Filtres optionnels
        if ($request->filled('date_debut')) {
            $query->whereDate('date_reservation', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_reservation', '<=', $request->date_fin);
        }

        $reservations = $query->orderBy('date_reservation', 'desc')->get();

        $filename = 'reservations_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($reservations) {
            $file = fopen('php://output', 'w');
            
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, [
                'ID',
                'Date',
                'Heure',
                'Client',
                'Email client',
                'Entreprise',
                'Service',
                'Prix',
                'Statut',
                'Payée',
                'Date création',
            ], ';');

            foreach ($reservations as $reservation) {
                fputcsv($file, [
                    $reservation->id,
                    $reservation->date_reservation?->format('d/m/Y'),
                    $reservation->heure_debut,
                    $reservation->user?->name ?? $reservation->nom_client,
                    $reservation->user?->email ?? $reservation->email_client,
                    $reservation->entreprise?->nom ?? '-',
                    $reservation->type_service ?? '-',
                    number_format($reservation->prix ?? 0, 2) . '€',
                    $reservation->statut,
                    $reservation->est_paye ? 'Oui' : 'Non',
                    $reservation->created_at->format('d/m/Y H:i'),
                ], ';');
            }

            fclose($file);
        };

        ActivityLog::log('export', 'Export des réservations (' . $reservations->count() . ' entrées)');

        return Response::stream($callback, 200, $headers);
    }
}
