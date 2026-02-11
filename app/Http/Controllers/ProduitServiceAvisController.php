<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\Produit;
use App\Models\TypeService;
use App\Models\ProduitAvis;
use App\Models\ServiceAvis;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProduitServiceAvisController extends Controller
{
    /**
     * Créer un avis pour un produit
     */
    public function storeProduitAvis(Request $request, $slug, $produitId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        $produit = Produit::where('id', $produitId)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $validated = $request->validate([
            'note' => 'required|integer|min:1|max:5',
            'commentaire' => 'nullable|string|max:2000',
            'reservation_id' => 'nullable|exists:reservations,id',
        ]);

        // Vérifier si l'utilisateur a déjà laissé un avis pour ce produit
        $existingAvis = ProduitAvis::where('user_id', $user->id)
            ->where('produit_id', $produit->id)
            ->first();

        if ($existingAvis) {
            return back()->withErrors(['error' => 'Vous avez déjà laissé un avis pour ce produit.']);
        }

        // Vérifier si reservation_id est fourni et appartient à l'utilisateur
        $reservationId = null;
        if ($request->filled('reservation_id')) {
            $reservation = Reservation::where('id', $request->reservation_id)
                ->where('user_id', $user->id)
                ->where('entreprise_id', $entreprise->id)
                ->first();
            
            if ($reservation) {
                $reservationId = $reservation->id;
            }
        }

        $avis = ProduitAvis::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'produit_id' => $produit->id,
            'reservation_id' => $reservationId,
            'note' => $validated['note'],
            'commentaire' => $validated['commentaire'] ?? null,
            'est_approuve' => true, // Par défaut approuvé, peut être modéré plus tard
        ]);

        return back()->with('success', 'Votre avis a été enregistré avec succès.');
    }

    /**
     * Créer un avis pour un service
     */
    public function storeServiceAvis(Request $request, $slug, $serviceId)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        
        $service = TypeService::where('id', $serviceId)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $validated = $request->validate([
            'note' => 'required|integer|min:1|max:5',
            'commentaire' => 'nullable|string|max:2000',
            'reservation_id' => 'nullable|exists:reservations,id',
        ]);

        // Vérifier si l'utilisateur a déjà laissé un avis pour ce service
        $existingAvis = ServiceAvis::where('user_id', $user->id)
            ->where('type_service_id', $service->id)
            ->first();

        if ($existingAvis) {
            return back()->withErrors(['error' => 'Vous avez déjà laissé un avis pour ce service.']);
        }

        // Vérifier si reservation_id est fourni et appartient à l'utilisateur
        $reservationId = null;
        if ($request->filled('reservation_id')) {
            $reservation = Reservation::where('id', $request->reservation_id)
                ->where('user_id', $user->id)
                ->where('entreprise_id', $entreprise->id)
                ->first();
            
            if ($reservation) {
                $reservationId = $reservation->id;
            }
        }

        $avis = ServiceAvis::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'type_service_id' => $service->id,
            'reservation_id' => $reservationId,
            'note' => $validated['note'],
            'commentaire' => $validated['commentaire'] ?? null,
            'est_approuve' => true, // Par défaut approuvé, peut être modéré plus tard
        ]);

        return back()->with('success', 'Votre avis a été enregistré avec succès.');
    }
}
