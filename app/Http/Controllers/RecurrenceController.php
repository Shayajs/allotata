<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\Recurrence;
use App\Models\Notification;
use App\Services\RecurrenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecurrenceController extends Controller
{
    /**
     * Liste des récurrences pour l'entreprise
     */
    public function index($slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403);
        }

        $recurrences = Recurrence::where('entreprise_id', $entreprise->id)
            ->with(['typeService', 'user', 'membre'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('recurrences.index', compact('entreprise', 'recurrences'));
    }

    /**
     * Détail d'une récurrence avec ses occurrences
     */
    public function show($slug, $id)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403);
        }

        $recurrence = Recurrence::where('id', $id)
            ->where('entreprise_id', $entreprise->id)
            ->with(['typeService', 'user', 'membre', 'reservations'])
            ->firstOrFail();

        return view('recurrences.show', compact('entreprise', 'recurrence'));
    }

    /**
     * Annuler une récurrence (annule les occurrences futures)
     */
    public function destroy($slug, $id)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403);
        }

        $recurrence = Recurrence::where('id', $id)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $service = app(RecurrenceService::class);
        $nbAnnulees = $service->annulerOccurrencesFutures($recurrence);

        return redirect()->route('recurrences.index', $slug)
            ->with('success', "Récurrence annulée. {$nbAnnulees} occurrence(s) future(s) annulée(s).");
    }
}
