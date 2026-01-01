<?php

namespace App\Http\Controllers;

use App\Models\Avis;
use App\Models\Entreprise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AvisController extends Controller
{
    /**
     * Vérifie si un utilisateur peut laisser un avis pour une entreprise
     * Conditions : réservation payée OU réservation passée (date réservation < maintenant) et confirmée
     */
    private function peutLaisserAvis($user, $entreprise): bool
    {
        return \App\Models\Reservation::where('user_id', $user->id)
            ->where('entreprise_id', $entreprise->id)
            ->where(function($query) {
                // Réservation payée
                $query->where('est_paye', true)
                      // OU réservation passée (date réservation < maintenant) et confirmée
                      ->orWhere(function($q) {
                          $q->where('date_reservation', '<', now())
                            ->where('statut', 'confirmee');
                      })
                      // OU réservation terminée
                      ->orWhere('statut', 'terminee');
            })
            ->exists();
    }

    /**
     * Afficher le formulaire pour laisser un avis
     */
    public function create($slug)
    {
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        $user = Auth::user();

        // Vérifier si l'utilisateur peut laisser un avis
        if (!$this->peutLaisserAvis($user, $entreprise)) {
            return redirect()->route('public.entreprise', $slug)
                ->with('error', 'Vous devez avoir au moins une réservation validée et payée pour pouvoir laisser un avis.');
        }

        // Vérifier si l'utilisateur a déjà laissé un avis
        $avisExistant = Avis::where('user_id', $user->id)
            ->where('entreprise_id', $entreprise->id)
            ->first();

        // Vérifier si l'utilisateur a des réservations payées et terminées avec cette entreprise
        $reservations = $user->reservations()
            ->where('entreprise_id', $entreprise->id)
            ->where('est_paye', true)
            ->where('statut', 'terminee')
            ->orderBy('date_reservation', 'desc')
            ->get();

        return view('avis.create', [
            'entreprise' => $entreprise,
            'avisExistant' => $avisExistant,
            'reservations' => $reservations,
        ]);
    }

    /**
     * Enregistrer un avis
     */
    public function store(Request $request, $slug)
    {
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        $user = Auth::user();

        // Vérifier si l'utilisateur peut laisser un avis
        if (!$this->peutLaisserAvis($user, $entreprise)) {
            return redirect()->route('public.entreprise', $slug)
                ->with('error', 'Vous devez avoir au moins une réservation validée et payée pour pouvoir laisser un avis.');
        }

        // Vérifier si l'utilisateur a déjà laissé un avis
        $avisExistant = Avis::where('user_id', $user->id)
            ->where('entreprise_id', $entreprise->id)
            ->first();

        if ($avisExistant) {
            return back()->withErrors(['error' => 'Vous avez déjà laissé un avis pour cette entreprise.']);
        }

        $validated = $request->validate([
            'note' => ['required', 'integer', 'min:1', 'max:5'],
            'commentaire' => ['nullable', 'string', 'max:1000'],
            'reservation_id' => ['nullable', 'exists:reservations,id'],
        ]);

        // Vérifier que la réservation appartient bien à l'utilisateur et à l'entreprise
        if ($request->reservation_id) {
            $reservation = \App\Models\Reservation::where('id', $request->reservation_id)
                ->where('user_id', $user->id)
                ->where('entreprise_id', $entreprise->id)
                ->first();

            if (!$reservation) {
                return back()->withErrors(['reservation_id' => 'Réservation invalide.']);
            }
        }

        Avis::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'reservation_id' => $validated['reservation_id'] ?? null,
            'note' => $validated['note'],
            'commentaire' => $validated['commentaire'] ?? null,
            'est_approuve' => true, // Par défaut approuvé
        ]);

        return redirect()->route('public.entreprise', $slug)
            ->with('success', 'Votre avis a été enregistré avec succès !');
    }

    /**
     * Mettre à jour un avis existant
     */
    public function update(Request $request, $slug, $id)
    {
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();
        $user = Auth::user();

        $avis = Avis::where('id', $id)
            ->where('user_id', $user->id)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $validated = $request->validate([
            'note' => ['required', 'integer', 'min:1', 'max:5'],
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ]);

        $avis->update($validated);

        return redirect()->route('public.entreprise', $slug)
            ->with('success', 'Votre avis a été mis à jour avec succès !');
    }
}
