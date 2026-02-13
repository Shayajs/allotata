<?php

namespace App\Http\Controllers;

use App\Models\Devis;
use App\Models\Entreprise;
use App\Models\TypeService;
use App\Models\Notification;
use App\Services\DevisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DevisController extends Controller
{
    /**
     * Liste des devis pour l'entreprise (côté prestataire)
     */
    public function index($slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403);
        }

        $devis = Devis::where('entreprise_id', $entreprise->id)
            ->with(['typeService', 'user', 'reservation'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('devis.index', compact('entreprise', 'devis'));
    }

    /**
     * Détail d'un devis (côté prestataire)
     */
    public function show($slug, $id)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403);
        }

        $devisItem = Devis::where('id', $id)
            ->where('entreprise_id', $entreprise->id)
            ->with(['typeService', 'user', 'reservation'])
            ->firstOrFail();

        return view('devis.show', compact('entreprise', 'devisItem'));
    }

    /**
     * Le client soumet une demande de devis (côté public)
     */
    public function store(Request $request, $slug)
    {
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        $userId = Auth::id();
        if (!$userId) {
            return redirect()->route('login')
                ->with('error', 'Vous devez être connecté pour demander un devis.');
        }

        $validated = $request->validate([
            'type_service_id' => 'required|exists:types_services,id',
            'description_besoin' => 'required|string|min:10|max:2000',
            'telephone_client' => 'required|string|max:20',
        ]);

        // Vérifier que le service appartient à l'entreprise et est sur devis
        $typeService = TypeService::where('id', $validated['type_service_id'])
            ->where('entreprise_id', $entreprise->id)
            ->where('est_actif', true)
            ->firstOrFail();

        if (!$typeService->estSurDevis()) {
            return back()->withErrors(['error' => 'Ce service n\'est pas de type "sur devis".']);
        }

        $devis = Devis::create([
            'entreprise_id' => $entreprise->id,
            'user_id' => $userId,
            'type_service_id' => $typeService->id,
            'description_besoin' => $validated['description_besoin'],
            'telephone_client' => $validated['telephone_client'],
            'statut' => 'en_attente',
        ]);

        // Notification au gérant
        $gerant = $entreprise->user;
        if ($gerant) {
            $nomClient = Auth::user()->name;
            Notification::creer(
                $gerant->id,
                'devis',
                'Nouvelle demande de devis',
                "{$nomClient} a demandé un devis pour {$typeService->nom}.",
                route('devis.show', [$slug, $devis->id]),
                ['devis_id' => $devis->id]
            );
        }

        return redirect()->route('public.entreprise', $slug)
            ->with('success', 'Votre demande de devis a été envoyée ! Le prestataire vous fera une proposition.');
    }

    /**
     * Le prestataire propose un montant/date (côté prestataire)
     */
    public function proposer(Request $request, $slug, $id)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403);
        }

        $validated = $request->validate([
            'montant_propose' => 'required|numeric|min:0',
            'type_structure_propose' => 'required|in:ponctuel,multi_jours,multi_rendez_vous',
            'date_proposee' => 'required|date|after:now',
            'heure_proposee' => 'required|date_format:H:i',
            'duree_proposee_minutes' => 'required|integer|min:1',
            'notes_prestataire' => 'nullable|string|max:2000',
        ]);

        $devisItem = Devis::where('id', $id)
            ->where('entreprise_id', $entreprise->id)
            ->firstOrFail();

        $dateProposee = $validated['date_proposee'] . ' ' . $validated['heure_proposee'];

        $service = app(DevisService::class);
        $service->proposer($devisItem, [
            'montant_propose' => $validated['montant_propose'],
            'type_structure_propose' => $validated['type_structure_propose'],
            'date_proposee' => $dateProposee,
            'duree_proposee_minutes' => $validated['duree_proposee_minutes'],
            'notes_prestataire' => $validated['notes_prestataire'] ?? null,
        ]);

        // Notification au client
        if ($devisItem->user_id) {
            Notification::creer(
                $devisItem->user_id,
                'devis',
                'Proposition de devis reçue',
                "{$entreprise->nom} vous a fait une proposition pour votre devis.",
                route('public.entreprise', $slug),
                ['devis_id' => $devisItem->id]
            );
        }

        return redirect()->route('devis.show', [$slug, $id])
            ->with('success', 'La proposition a été envoyée au client.');
    }

    /**
     * Le client accepte le devis -> conversion auto en réservation
     */
    public function accepter(Request $request, $slug, $id)
    {
        $userId = Auth::id();
        if (!$userId) {
            return redirect()->route('login');
        }

        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        $devisItem = Devis::where('id', $id)
            ->where('entreprise_id', $entreprise->id)
            ->where('user_id', $userId)
            ->firstOrFail();

        try {
            $service = app(DevisService::class);
            $reservation = $service->accepter($devisItem);

            // Notification au gérant
            $gerant = $entreprise->user;
            if ($gerant) {
                Notification::creer(
                    $gerant->id,
                    'devis',
                    'Devis accepté',
                    "Le client a accepté le devis #{$devisItem->id}. Une réservation a été créée automatiquement.",
                    route('reservations.show', [$slug, $reservation->id]),
                    ['devis_id' => $devisItem->id, 'reservation_id' => $reservation->id]
                );
            }

            return redirect()->route('public.entreprise', $slug)
                ->with('success', 'Devis accepté ! Votre réservation a été créée.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Le client refuse le devis
     */
    public function refuser(Request $request, $slug, $id)
    {
        $userId = Auth::id();
        if (!$userId) {
            return redirect()->route('login');
        }

        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        $devisItem = Devis::where('id', $id)
            ->where('entreprise_id', $entreprise->id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $service = app(DevisService::class);
        $service->refuser($devisItem);

        // Notification au gérant
        $gerant = $entreprise->user;
        if ($gerant) {
            Notification::creer(
                $gerant->id,
                'devis',
                'Devis refusé',
                "Le client a refusé le devis #{$devisItem->id}.",
                route('devis.show', [$slug, $devisItem->id]),
                ['devis_id' => $devisItem->id]
            );
        }

        return redirect()->route('public.entreprise', $slug)
            ->with('info', 'Vous avez refusé le devis.');
    }
}
