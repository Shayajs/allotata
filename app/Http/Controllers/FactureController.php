<?php

namespace App\Http\Controllers;

use App\Models\Facture;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FactureController extends Controller
{
    /**
     * Afficher les factures du client
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Générer automatiquement les factures pour les réservations payées sans facture
        $reservationsPayeesSansFacture = Reservation::where('user_id', $user->id)
            ->where('est_paye', true)
            ->whereDoesntHave('facture')
            ->with(['entreprise'])
            ->get();
        
        foreach ($reservationsPayeesSansFacture as $reservation) {
            try {
                Facture::generateFromReservation($reservation);
            } catch (\Exception $e) {
                \Log::error("Erreur lors de la génération automatique de facture pour la réservation #{$reservation->id}: " . $e->getMessage());
            }
        }
        
        // Générer automatiquement les factures d'abonnement manuel si nécessaire
        if ($user->abonnement_manuel && $user->abonnement_manuel_type_renouvellement && $user->abonnement_manuel_jour_renouvellement) {
            try {
                // Vérifier si une facture doit être générée aujourd'hui
                $jourActuel = now()->day;
                if ($jourActuel == $user->abonnement_manuel_jour_renouvellement) {
                    // Vérifier si une facture n'existe pas déjà pour ce mois/année
                    $periodeDebut = now()->copy()->startOfMonth();
                    $periodeFin = now()->copy()->endOfMonth();
                    
                    if ($user->abonnement_manuel_type_renouvellement === 'annuel') {
                        $periodeDebut = now()->copy()->startOfYear();
                        $periodeFin = now()->copy()->endOfYear();
                    }

                    $factureExistante = Facture::where('user_id', $user->id)
                        ->where('type_facture', 'abonnement_manuel')
                        ->whereBetween('date_facture', [$periodeDebut, $periodeFin])
                        ->first();

                    if (!$factureExistante) {
                        Facture::generateFromManualSubscription($user);
                    }
                }
            } catch (\Exception $e) {
                \Log::error("Erreur lors de la génération automatique de facture d'abonnement manuel pour l'utilisateur #{$user->id}: " . $e->getMessage());
            }
        }

        $query = $user->factures()
            ->with(['entreprise', 'reservation', 'entrepriseSubscription']);

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero_facture', 'like', "%{$search}%")
                  ->orWhereHas('entreprise', function($entrepriseQuery) use ($search) {
                      $entrepriseQuery->where('nom', 'like', "%{$search}%");
                  });
            });
        }

        // Filtre par statut
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Filtre par date
        if ($request->filled('date_debut')) {
            $query->whereDate('date_facture', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_facture', '<=', $request->date_fin);
        }

        $factures = $query->orderBy('date_facture', 'desc')->paginate(15)->withQueryString();

        return view('factures.index', [
            'factures' => $factures,
            'user' => $user,
        ]);
    }

    /**
     * Afficher une facture (client)
     */
    public function show($id)
    {
        $user = Auth::user();
        $facture = Facture::where('user_id', $user->id)
            ->with(['entreprise', 'reservation', 'reservations.user', 'reservations.typeService', 'user'])
            ->findOrFail($id);

        return view('factures.show', [
            'facture' => $facture,
        ]);
    }

    /**
     * Afficher les factures d'une entreprise (gérant)
     */
    public function indexEntreprise(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = \App\Models\Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        // Générer automatiquement les factures pour les réservations payées sans facture
        $reservationsPayeesSansFacture = Reservation::where('entreprise_id', $entreprise->id)
            ->where('est_paye', true)
            ->whereDoesntHave('facture')
            ->with(['user'])
            ->get();
        
        foreach ($reservationsPayeesSansFacture as $reservation) {
            try {
                Facture::generateFromReservation($reservation);
            } catch (\Exception $e) {
                \Log::error("Erreur lors de la génération automatique de facture pour la réservation #{$reservation->id}: " . $e->getMessage());
            }
        }

        $query = $entreprise->factures()
            ->with(['user', 'reservation']);

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('numero_facture', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filtre par statut
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Filtre par date
        if ($request->filled('date_debut')) {
            $query->whereDate('date_facture', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_facture', '<=', $request->date_fin);
        }

        $factures = $query->orderBy('date_facture', 'desc')->paginate(15)->withQueryString();

        return view('factures.entreprise', [
            'factures' => $factures,
            'entreprise' => $entreprise,
        ]);
    }

    /**
     * Afficher une facture (gérant)
     */
    public function showEntreprise($slug, $id)
    {
        $user = Auth::user();
        $entreprise = \App\Models\Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $facture = Facture::where('entreprise_id', $entreprise->id)
            ->with(['entreprise', 'reservation', 'reservations.user', 'reservations.typeService', 'user'])
            ->findOrFail($id);

        return view('factures.show', [
            'facture' => $facture,
            'isGerant' => true,
        ]);
    }

    /**
     * Télécharger une facture en PDF
     */
    public function download($id)
    {
        $user = Auth::user();
        $facture = Facture::where('user_id', $user->id)
            ->with(['entreprise', 'reservation', 'reservations.user', 'reservations.typeService', 'user'])
            ->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('factures.pdf', compact('facture'));
        
        return $pdf->download('facture-' . $facture->numero_facture . '.pdf');
    }

    /**
     * Télécharger une facture en PDF (gérant)
     */
    public function downloadEntreprise($slug, $id)
    {
        $user = Auth::user();
        $entreprise = \App\Models\Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $facture = Facture::where('entreprise_id', $entreprise->id)
            ->with(['entreprise', 'reservation', 'reservations.user', 'reservations.typeService', 'user'])
            ->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('factures.pdf', compact('facture'));
        
        return $pdf->download('facture-' . $facture->numero_facture . '.pdf');
    }

    /**
     * Page de comptabilité pour une entreprise
     */
    public function comptabilite(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = \App\Models\Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        // Période par défaut : année en cours
        $dateDebut = $request->filled('date_debut') ? $request->date_debut : now()->startOfYear()->format('Y-m-d');
        $dateFin = $request->filled('date_fin') ? $request->date_fin : now()->endOfYear()->format('Y-m-d');

        // Récupérer toutes les factures de la période
        $factures = $entreprise->factures()
            ->whereBetween('date_facture', [$dateDebut, $dateFin])
            ->with(['user', 'reservation'])
            ->orderBy('date_facture', 'asc')
            ->get();

        // Calculs
        $totalHT = $factures->sum('montant_ht');
        $totalTVA = $factures->sum('montant_tva');
        $totalTTC = $factures->sum('montant_ttc');
        
        // Par statut
        $facturesEmises = $factures->where('statut', 'emise');
        $facturesPayees = $factures->where('statut', 'payee');
        $facturesAnnulees = $factures->where('statut', 'annulee');
        
        $totalHTEmises = $facturesEmises->sum('montant_ht');
        $totalTTCEmises = $facturesEmises->sum('montant_ttc');
        $totalHTPayees = $facturesPayees->sum('montant_ht');
        $totalTTCPayees = $facturesPayees->sum('montant_ttc');
        $totalHTAnnulees = $facturesAnnulees->sum('montant_ht');
        $totalTTCAnnulees = $facturesAnnulees->sum('montant_ttc');

        // Par mois
        $facturesParMois = $factures->groupBy(function($facture) {
            return $facture->date_facture->format('Y-m');
        })->map(function($facturesMois) {
            return [
                'count' => $facturesMois->count(),
                'ht' => $facturesMois->sum('montant_ht'),
                'ttc' => $facturesMois->sum('montant_ttc'),
            ];
        });

        return view('factures.comptabilite', [
            'entreprise' => $entreprise,
            'factures' => $factures,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'totalHT' => $totalHT,
            'totalTVA' => $totalTVA,
            'totalTTC' => $totalTTC,
            'facturesEmises' => $facturesEmises,
            'facturesPayees' => $facturesPayees,
            'facturesAnnulees' => $facturesAnnulees,
            'totalHTEmises' => $totalHTEmises,
            'totalTTCEmises' => $totalTTCEmises,
            'totalHTPayees' => $totalHTPayees,
            'totalTTCPayees' => $totalTTCPayees,
            'totalHTAnnulees' => $totalHTAnnulees,
            'totalTTCAnnulees' => $totalTTCAnnulees,
            'facturesParMois' => $facturesParMois,
        ]);
    }

    /**
     * Afficher le formulaire de création de facture groupée (gérant)
     */
    public function createGroupee($slug)
    {
        $user = Auth::user();
        $entreprise = \App\Models\Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        // Récupérer les types de services et les clients pour les filtres
        $typesServices = \App\Models\TypeService::where('entreprise_id', $entreprise->id)
            ->where('est_actif', true)
            ->get();

        $clients = \App\Models\User::whereHas('reservations', function($query) use ($entreprise) {
            $query->where('entreprise_id', $entreprise->id)
                  ->where('est_paye', true);
        })->get();

        return view('factures.create-groupee', [
            'entreprise' => $entreprise,
            'typesServices' => $typesServices,
            'clients' => $clients,
        ]);
    }

    /**
     * API : Récupérer les réservations payées sans facture avec filtres (gérant)
     */
    public function getReservationsPourFactureGroupee(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = \App\Models\Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $query = Reservation::where('entreprise_id', $entreprise->id)
            ->where('est_paye', true)
            ->whereDoesntHave('facture')
            ->whereDoesntHave('facturesGroupes')
            ->with(['user', 'typeService']);

        // Filtre par client
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filtre par type de service
        if ($request->filled('type_service_id')) {
            $query->where('type_service_id', $request->type_service_id);
        }

        // Filtre par date (mois)
        if ($request->filled('mois')) {
            $mois = \Carbon\Carbon::parse($request->mois . '-01');
            $query->whereYear('date_reservation', $mois->year)
                  ->whereMonth('date_reservation', $mois->month);
        }

        // Filtre par date (jour)
        if ($request->filled('date_debut')) {
            $query->whereDate('date_reservation', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_reservation', '<=', $request->date_fin);
        }

        // Filtre par statut de réservation
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $reservations = $query->orderBy('date_reservation', 'desc')->get();

        return response()->json([
            'reservations' => $reservations->map(function($reservation) {
                return [
                    'id' => $reservation->id,
                    'date' => $reservation->date_reservation->format('d/m/Y H:i'),
                    'client' => $reservation->user ? $reservation->user->name : ($reservation->nom_client ?? 'Client non inscrit'),
                    'client_email' => $reservation->user ? $reservation->user->email : ($reservation->email_client ?? 'N/A'),
                    'service' => $reservation->typeService ? $reservation->typeService->nom : ($reservation->type_service ?? 'N/A'),
                    'prix' => number_format($reservation->prix, 2, ',', ' ') . ' €',
                    'statut' => $reservation->statut,
                ];
            }),
            'total' => $reservations->sum('prix'),
            'count' => $reservations->count(),
        ]);
    }

    /**
     * Créer une facture groupée (gérant)
     */
    public function storeGroupee(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = \App\Models\Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $validated = $request->validate([
            'reservation_ids' => 'required|array|min:1',
            'reservation_ids.*' => 'exists:reservations,id',
            'taux_tva' => 'nullable|numeric|min:0|max:100',
        ]);

        // Vérifier que toutes les réservations appartiennent à l'entreprise et sont payées
        $reservations = Reservation::whereIn('id', $validated['reservation_ids'])
            ->where('entreprise_id', $entreprise->id)
            ->where('est_paye', true)
            ->get();

        if ($reservations->count() !== count($validated['reservation_ids'])) {
            return back()->withErrors(['error' => 'Certaines réservations ne sont pas valides.']);
        }

        // Vérifier qu'aucune n'a déjà une facture
        foreach ($reservations as $reservation) {
            if ($reservation->aDejaFacture()) {
                return back()->withErrors(['error' => 'Certaines réservations ont déjà une facture.']);
            }
        }

        // Récupérer le user_id (doit être le même pour toutes les réservations)
        $userId = $reservations->first()->user_id;
        
        // Vérifier que toutes les réservations ont un user_id
        if (!$userId) {
            return back()->withErrors(['error' => 'Les réservations doivent avoir un client associé pour créer une facture groupée.']);
        }
        
        if ($reservations->pluck('user_id')->unique()->count() > 1) {
            return back()->withErrors(['error' => 'Toutes les réservations doivent être du même client.']);
        }

        $tauxTVA = $validated['taux_tva'] ?? 0;

        try {
            $facture = Facture::generateFromReservations(
                $validated['reservation_ids'],
                $entreprise->id,
                $userId,
                $tauxTVA
            );

            if ($facture) {
                return redirect()->route('factures.entreprise.show', [$slug, $facture->id])
                    ->with('success', 'Facture groupée créée avec succès.');
            } else {
                return back()->withErrors(['error' => 'Impossible de créer la facture groupée.']);
            }
        } catch (\Exception $e) {
            \Log::error("Erreur lors de la création de facture groupée: " . $e->getMessage());
            return back()->withErrors(['error' => 'Une erreur est survenue lors de la création de la facture.']);
        }
    }
}
