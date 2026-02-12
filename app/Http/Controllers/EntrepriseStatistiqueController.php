<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\EntrepriseVisite;
use App\Models\VisiteClic;
use App\Models\CustomPrice;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\GenericSmsNotification;
use App\Mail\GenericEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class EntrepriseStatistiqueController extends Controller
{
    /**
     * Afficher l'onglet statistiques
     */
    public function index(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        // Calculer les statistiques
        $stats = $this->calculerStatistiques($entreprise);

        // Récupérer les visiteurs sans réservation
        $visiteursSansReservation = EntrepriseVisite::visiteursSansReservation($entreprise->id, 30);

        // Top services et produits cliqués
        $topServices = $this->getTopServices($entreprise->id, 30);
        $topProduits = $this->getTopProduits($entreprise->id, 30);

        return view('entreprise.dashboard.tabs.statistiques', [
            'entreprise' => $entreprise,
            'stats' => $stats,
            'visiteursSansReservation' => $visiteursSansReservation,
            'topServices' => $topServices,
            'topProduits' => $topProduits,
        ]);
    }

    /**
     * API pour données en temps réel
     */
    public function apiStats(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $stats = $this->calculerStatistiques($entreprise);
        $visiteursSansReservation = EntrepriseVisite::visiteursSansReservation($entreprise->id, 30);
        $topServices = $this->getTopServices($entreprise->id, 30);
        $topProduits = $this->getTopProduits($entreprise->id, 30);

        return response()->json([
            'stats' => $stats,
            'visiteurs_count' => $visiteursSansReservation->count(),
            'top_services' => $topServices,
            'top_produits' => $topProduits,
            'updated_at' => now()->setTimezone('Europe/Paris')->format('H:i:s'),
        ]);
    }

    /**
     * Calculer les statistiques
     */
    public function calculerStatistiques(Entreprise $entreprise): array
    {
        return Cache::remember("entreprise_stats_visites_{$entreprise->id}", 300, function () use ($entreprise) {
            $periodDays = 30;
            $dateDebut = now()->subDays($periodDays);

            $visites = EntrepriseVisite::where('entreprise_id', $entreprise->id)
                ->where('created_at', '>=', $dateDebut)
                ->get();

            // Statistiques principales
            $totalVisites = $visites->count();
            $visitesExploration = $visites->where('a_quitte_apres_exploration', true)->count();
            $visitesRapides = $visites->where('a_quitte_rapidement', true)->count();
            $reservations = $visites->where('a_passe_commande', true)->count();

            // Taux de conversion
            $tauxConversion = $totalVisites > 0 
                ? round(($reservations / $totalVisites) * 100, 1) 
                : 0;

            // Temps moyen avant réservation
            $tempsMoyenAvantReservation = $visites
                ->where('temps_avant_reservation_secondes', '>', 0)
                ->avg('temps_avant_reservation_secondes');

            // Évolution des visites (30 derniers jours)
            $evolutionVisites = [];
            for ($i = $periodDays - 1; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $count = $visites->filter(function($v) use ($date) {
                    return $v->created_at->format('Y-m-d') === $date;
                })->count();
                $evolutionVisites[] = [
                    'date' => $date,
                    'count' => $count,
                ];
            }

            // Répartition par type de page
            $repartitionPages = [
                'accueil' => $visites->where('page_type', 'accueil')->count(),
                'agenda' => $visites->where('page_type', 'agenda')->count(),
                'store' => $visites->where('page_type', 'store')->count(),
                'services' => $visites->where('page_type', 'services')->count(),
                'produits' => $visites->where('page_type', 'produits')->count(),
            ];

            // Temps moyen par page
            $tempsMoyenParPage = [
                'accueil' => $visites->where('page_type', 'accueil')->where('duree_seconde', '>', 0)->avg('duree_seconde') ?? 0,
                'agenda' => $visites->where('page_type', 'agenda')->where('duree_seconde', '>', 0)->avg('duree_seconde') ?? 0,
                'store' => $visites->where('page_type', 'store')->where('duree_seconde', '>', 0)->avg('duree_seconde') ?? 0,
                'services' => $visites->where('page_type', 'services')->where('duree_seconde', '>', 0)->avg('duree_seconde') ?? 0,
                'produits' => $visites->where('page_type', 'produits')->where('duree_seconde', '>', 0)->avg('duree_seconde') ?? 0,
            ];

            // Taux de rebond
            $tauxRebond = $totalVisites > 0 
                ? round(($visitesRapides / $totalVisites) * 100, 1) 
                : 0;

            return [
                'total_visites' => $totalVisites,
                'visites_exploration' => $visitesExploration,
                'visites_rapides' => $visitesRapides,
                'reservations' => $reservations,
                'taux_conversion' => $tauxConversion,
                'temps_moyen_avant_reservation' => round($tempsMoyenAvantReservation ?? 0),
                'evolution_visites' => $evolutionVisites,
                'repartition_pages' => $repartitionPages,
                'temps_moyen_par_page' => $tempsMoyenParPage,
                'taux_rebond' => $tauxRebond,
            ];
        });
    }

    /**
     * Top services cliqués
     */
    public function getTopServices(int $entrepriseId, int $periodDays): array
    {
        $dateDebut = now()->subDays($periodDays);

        $result = VisiteClic::whereHas('visite', function($query) use ($entrepriseId, $dateDebut) {
                $query->where('entreprise_id', $entrepriseId)
                      ->where('created_at', '>=', $dateDebut);
            })
            ->where('type', 'service')
            ->selectRaw('item_id, item_nom, COUNT(*) as nb_clics')
            ->groupBy('item_id', 'item_nom')
            ->orderBy('nb_clics', 'desc')
            ->limit(5)
            ->get();

        return $result->map(function($item) {
                return [
                    'id' => (int) $item->item_id,
                    'nom' => $item->item_nom,
                    'nb_clics' => (int) $item->nb_clics,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Top produits cliqués
     */
    public function getTopProduits(int $entrepriseId, int $periodDays): array
    {
        $dateDebut = now()->subDays($periodDays);

        $result = VisiteClic::whereHas('visite', function($query) use ($entrepriseId, $dateDebut) {
                $query->where('entreprise_id', $entrepriseId)
                      ->where('created_at', '>=', $dateDebut);
            })
            ->where('type', 'produit')
            ->selectRaw('item_id, item_nom, COUNT(*) as nb_clics')
            ->groupBy('item_id', 'item_nom')
            ->orderBy('nb_clics', 'desc')
            ->limit(5)
            ->get();

        return $result->map(function($item) {
                return [
                    'id' => (int) $item->item_id,
                    'nom' => $item->item_nom,
                    'nb_clics' => (int) $item->nb_clics,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Contacter un visiteur
     */
    public function contacterVisiteur(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $validated = $request->validate([
            'visite_id' => 'required|exists:entreprise_visites,id',
            'type_contact' => 'required|in:sms,email,messagerie',
            'message' => 'required|string|max:1000',
        ]);

        $visite = EntrepriseVisite::with('user')->findOrFail($validated['visite_id']);

        // Vérifier que la visite appartient à l'entreprise
        if ($visite->entreprise_id !== $entreprise->id) {
            abort(403, 'Cette visite n\'appartient pas à votre entreprise.');
        }

        // Vérifier que le visiteur est connecté
        if (!$visite->user_id) {
            return back()->withErrors(['error' => 'Ce visiteur n\'est pas connecté. Impossible de le contacter.']);
        }

        $visiteur = $visite->user;

        switch ($validated['type_contact']) {
            case 'sms':
                $this->envoyerSms($visiteur, $validated['message'], $entreprise);
                break;
            case 'email':
                $this->envoyerEmail($visiteur, $validated['message'], $entreprise);
                break;
            case 'messagerie':
                $this->ouvrirMessagerie($visiteur, $validated['message'], $entreprise);
                break;
        }

        return back()->with('success', 'Message envoyé avec succès !');
    }

    /**
     * Envoyer un SMS
     */
    private function envoyerSms(User $visiteur, string $message, Entreprise $entreprise): void
    {
        if (!$visiteur->telephone) {
            throw new \Exception('Le visiteur n\'a pas de numéro de téléphone.');
        }

        // Utiliser le système SMS existant
        $visiteur->notify(new GenericSmsNotification(
            "Bonjour, message de {$entreprise->nom} : {$message}"
        ));
    }

    /**
     * Envoyer un email
     */
    private function envoyerEmail(User $visiteur, string $message, Entreprise $entreprise): void
    {
        Mail::to($visiteur->email)->send(new GenericEmail(
            "Message de {$entreprise->nom}",
            $message
        ));
    }

    /**
     * Ouvrir/creer une conversation dans la messagerie
     */
    private function ouvrirMessagerie(User $visiteur, string $message, Entreprise $entreprise): void
    {
        $conversation = Conversation::firstOrCreate(
            [
                'user_id' => $visiteur->id,
                'entreprise_id' => $entreprise->id,
            ],
            [
                'est_archivee' => false,
            ]
        );

        // Envoyer le message
        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $entreprise->user_id, // Le gérant envoie le message
            'contenu' => $message,
            'est_lu' => false,
        ]);

        $conversation->update(['dernier_message_at' => now()]);
    }

    /**
     * Proposer un prix personnalisé
     */
    public function proposerPrixPersonnalise(Request $request, $slug)
    {
        $user = Auth::user();
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        // Vérifier les permissions
        if (!$entreprise->peutEtreGereePar($user) && !$user->is_admin) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $validated = $request->validate([
            'visite_id' => 'required|exists:entreprise_visites,id',
            'user_id' => 'required|exists:users,id',
            'service_id' => 'nullable|exists:types_services,id',
            'produit_id' => 'nullable|exists:produits,id',
            'prix' => 'required|numeric|min:0',
            'message' => 'nullable|string|max:500',
        ]);

        $visite = EntrepriseVisite::findOrFail($validated['visite_id']);

        // Vérifier que la visite appartient à l'entreprise
        if ($visite->entreprise_id !== $entreprise->id) {
            abort(403, 'Cette visite n\'appartient pas à votre entreprise.');
        }

        $visiteur = User::findOrFail($validated['user_id']);

        // Créer le prix personnalisé (pour les abonnements Stripe, on pourrait étendre cela)
        // Pour l'instant, on crée une conversation avec le prix proposé
        
        $conversation = Conversation::firstOrCreate(
            [
                'user_id' => $visiteur->id,
                'entreprise_id' => $entreprise->id,
            ],
            [
                'est_archivee' => false,
            ]
        );

        $messagePrix = "Bonjour, nous vous proposons un prix spécial de " . number_format($validated['prix'], 2, ',', ' ') . " €";
        if ($validated['service_id']) {
            $service = \App\Models\TypeService::find($validated['service_id']);
            $messagePrix .= " pour le service : {$service->nom}";
        }
        if ($validated['produit_id']) {
            $produit = \App\Models\Produit::find($validated['produit_id']);
            $messagePrix .= " pour le produit : {$produit->nom}";
        }
        if ($validated['message']) {
            $messagePrix .= "\n\n" . $validated['message'];
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $entreprise->user_id,
            'contenu' => $messagePrix,
            'est_lu' => false,
        ]);

        $conversation->update(['dernier_message_at' => now()]);

        return back()->with('success', 'Prix personnalisé proposé avec succès !');
    }
}
