<?php

namespace App\Http\Controllers;

use App\Models\Avis;
use App\Models\Entreprise;
use App\Models\RealisationPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

        // Charger les photos existantes si l'avis existe
        if ($avisExistant) {
            $avisExistant->load('photos');
        }

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
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
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

        $avis = Avis::create([
            'user_id' => $user->id,
            'entreprise_id' => $entreprise->id,
            'reservation_id' => $validated['reservation_id'] ?? null,
            'note' => $validated['note'],
            'commentaire' => $validated['commentaire'] ?? null,
            'est_approuve' => true, // Par défaut approuvé
        ]);

        // Gérer l'upload des photos
        if ($request->hasFile('photos')) {
            $this->handlePhotoUpload($request->file('photos'), $avis, $entreprise);
        }

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
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'photos_a_supprimer' => ['nullable', 'array'],
            'photos_a_supprimer.*' => ['integer', 'exists:realisation_photos,id'],
        ]);

        $avis->update([
            'note' => $validated['note'],
            'commentaire' => $validated['commentaire'] ?? null,
        ]);

        // Supprimer les photos marquées pour suppression
        if (!empty($validated['photos_a_supprimer'])) {
            $photosASupprimer = RealisationPhoto::whereIn('id', $validated['photos_a_supprimer'])
                ->where('avis_id', $avis->id)
                ->get();

            foreach ($photosASupprimer as $photo) {
                // Supprimer le fichier physique
                $filePath = public_path('media/' . $photo->photo_path);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $photo->delete();
            }
        }

        // Gérer l'upload des nouvelles photos
        if ($request->hasFile('photos')) {
            $this->handlePhotoUpload($request->file('photos'), $avis, $entreprise);
        }

        return redirect()->route('public.entreprise', $slug)
            ->with('success', 'Votre avis a été mis à jour avec succès !');
    }

    /**
     * Gérer l'upload des photos pour un avis
     */
    private function handlePhotoUpload(array $photos, Avis $avis, Entreprise $entreprise): void
    {
        $ordre = $avis->photos()->max('ordre') ?? 0;

        foreach ($photos as $photo) {
            $ordre++;
            $filename = 'avis_' . $avis->id . '_' . time() . '_' . $ordre . '.' . $photo->getClientOriginalExtension();
            $path = 'realisations/' . $entreprise->id;
            
            // Créer le dossier s'il n'existe pas
            $fullPath = public_path('media/' . $path);
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            // Déplacer le fichier
            $photo->move($fullPath, $filename);

            // Créer l'entrée en base de données
            RealisationPhoto::create([
                'entreprise_id' => $entreprise->id,
                'avis_id' => $avis->id,
                'photo_path' => $path . '/' . $filename,
                'titre' => 'Photo ajoutée par ' . $avis->user->name,
                'description' => $avis->commentaire ? substr($avis->commentaire, 0, 100) : null,
                'ordre' => $ordre,
            ]);
        }
    }
}
