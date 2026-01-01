<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Entreprise;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservationController extends Controller
{
    /**
     * Afficher les réservations en attente pour une entreprise
     */
    public function index(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $query = $entreprise->reservations()
            ->with(['user', 'typeService']);

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('type_service', 'like', "%{$search}%")
                  ->orWhere('lieu', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
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

        // Filtre par paiement
        if ($request->filled('est_paye')) {
            $query->where('est_paye', $request->est_paye === '1');
        }

        // Filtre par date
        if ($request->filled('date_debut')) {
            $query->whereDate('date_reservation', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_reservation', '<=', $request->date_fin);
        }

        $reservations = $query->with('membre.user')
            ->orderBy('date_reservation', 'asc')
            ->get()
            ->groupBy('statut');

        // Charger les membres si multi-personnes
        $membres = collect([]);
        if ($entreprise->aGestionMultiPersonnes()) {
            $membres = $entreprise->membres()
                ->where('est_actif', true)
                ->with('user')
                ->get();
        }

        return view('reservations.index', [
            'entreprise' => $entreprise,
            'reservations' => $reservations,
            'membres' => $membres,
            'aGestionMultiPersonnes' => $entreprise->aGestionMultiPersonnes(),
        ]);
    }

    /**
     * Afficher une réservation
     */
    public function show($slug, $id)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $reservation = Reservation::where('id', $id)
            ->where('entreprise_id', $entreprise->id)
            ->with(['user', 'typeService', 'membre.user'])
            ->firstOrFail();

        // Charger les membres si multi-personnes
        $membres = collect([]);
        if ($entreprise->aGestionMultiPersonnes()) {
            $membres = $entreprise->membres()
                ->where('est_actif', true)
                ->with('user')
                ->get();
        }

        return view('reservations.show', [
            'entreprise' => $entreprise,
            'reservation' => $reservation,
            'membres' => $membres,
            'aGestionMultiPersonnes' => $entreprise->aGestionMultiPersonnes(),
        ]);
    }

    /**
     * Accepter une réservation
     */
    public function accept(Request $request, $slug, $id)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $reservation = Reservation::where('id', $id)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $validated = $request->validate([
            'notes_gerant' => 'nullable|string|max:1000',
        ]);

        $reservation->update([
            'statut' => 'confirmee',
            'notes' => $reservation->notes . ($validated['notes_gerant'] ? "\n\n[Note de la tata] " . $validated['notes_gerant'] : ''),
        ]);

        // Créer une notification pour le client
        Notification::creer(
            $reservation->user_id,
            'reservation',
            'Réservation confirmée',
            "Votre réservation pour {$entreprise->nom} le {$reservation->date_reservation->format('d/m/Y à H:i')} a été confirmée !",
            route('dashboard'),
            ['reservation_id' => $reservation->id, 'entreprise_id' => $entreprise->id]
        );

        return redirect()->route('reservations.show', [$slug, $id])
            ->with('success', 'La réservation a été acceptée avec succès.');
    }

    /**
     * Refuser une réservation
     */
    public function reject(Request $request, $slug, $id)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $reservation = Reservation::where('id', $id)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $validated = $request->validate([
            'raison_refus' => 'nullable|string|max:500',
        ]);

        $reservation->update([
            'statut' => 'annulee',
            'notes' => $reservation->notes . ($validated['raison_refus'] ? "\n\n[Raison du refus] " . $validated['raison_refus'] : ''),
        ]);

        // Créer une notification pour le client
        $raison = $validated['raison_refus'] ? " Raison : {$validated['raison_refus']}" : '';
        Notification::creer(
            $reservation->user_id,
            'reservation',
            'Réservation annulée',
            "Votre réservation pour {$entreprise->nom} le {$reservation->date_reservation->format('d/m/Y à H:i')} a été annulée.{$raison}",
            route('dashboard'),
            ['reservation_id' => $reservation->id, 'entreprise_id' => $entreprise->id]
        );

        return redirect()->route('reservations.index', $slug)
            ->with('success', 'La réservation a été refusée.');
    }

    /**
     * Ajouter des notes à une réservation
     */
    public function addNotes(Request $request, $slug, $id)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $reservation = Reservation::where('id', $id)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $validated = $request->validate([
            'notes_gerant' => 'required|string|max:1000',
        ]);

        $notesActuelles = $reservation->notes ?? '';
        $reservation->update([
            'notes' => $notesActuelles . ($notesActuelles ? "\n\n" : '') . "[Note de la tata] " . $validated['notes_gerant'],
        ]);

        return redirect()->route('reservations.show', [$slug, $id])
            ->with('success', 'Les notes ont été ajoutées avec succès.');
    }

    /**
     * Marquer une réservation comme payée
     */
    public function marquerPayee(Request $request, $slug, $id)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)
            ->firstOrFail();
        
        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $reservation = Reservation::where('id', $id)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $validated = $request->validate([
            'date_paiement' => 'nullable|date',
            'notes_paiement' => 'nullable|string|max:500',
        ]);

        $datePaiement = $validated['date_paiement'] ?? now();

        $reservation->update([
            'est_paye' => true,
            'date_paiement' => $datePaiement,
            'notes' => $reservation->notes . ($validated['notes_paiement'] ? "\n\n[Paiement] " . $validated['notes_paiement'] : ''),
        ]);

        // Recharger la réservation pour avoir les dernières valeurs
        $reservation->refresh();
        
        // La facture sera générée automatiquement par l'observer ReservationObserver
        // Vérifier si une facture a été créée
        $factureGeneree = $reservation->facture;
        $message = 'Le paiement a été marqué comme effectué. Le client a été notifié.';
        if ($factureGeneree) {
            $message .= ' Une facture a été générée automatiquement.';
        } else {
            // Si l'observer n'a pas fonctionné, essayer de générer la facture manuellement
            try {
                $facture = \App\Models\Facture::generateFromReservation($reservation);
                if ($facture) {
                    $message .= ' Une facture a été générée.';
                }
            } catch (\Exception $e) {
                \Log::error('Erreur lors de la génération manuelle de la facture : ' . $e->getMessage());
            }
        }

        // Créer une notification pour le client
        Notification::creer(
            $reservation->user_id,
            'paiement',
            'Paiement confirmé',
            "Votre paiement de {$reservation->prix} € pour la réservation du {$reservation->date_reservation->format('d/m/Y')} a été confirmé par {$entreprise->nom}.",
            route('dashboard'),
            ['reservation_id' => $reservation->id, 'entreprise_id' => $entreprise->id]
        );

        return redirect()->route('reservations.show', [$slug, $id])
            ->with('success', $message);
    }
}
