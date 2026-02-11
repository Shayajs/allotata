<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleCalendarController extends Controller
{
    public function __construct(
        protected GoogleCalendarService $googleCalendarService
    ) {}

    /**
     * Redirige vers l'écran de consentement Google OAuth.
     */
    public function redirect(string $slug)
    {
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        // Vérifier que l'utilisateur peut gérer cette entreprise
        if (!$entreprise->peutEtreGereePar(auth()->user())) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        // Stocker le slug en session pour le callback
        session(['google_calendar_entreprise_slug' => $slug]);

        $authUrl = $this->googleCalendarService->getAuthUrl($entreprise);

        return redirect()->away($authUrl);
    }

    /**
     * Traite le retour de Google après le consentement OAuth.
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()
                ->route('entreprise.dashboard', ['slug' => session('google_calendar_entreprise_slug'), 'tab' => 'parametres'])
                ->with('error', 'La connexion à Google Calendar a été annulée.');
        }

        $code = $request->get('code');
        $entrepriseId = $request->get('state'); // L'ID de l'entreprise qu'on a passé en state

        if (!$code || !$entrepriseId) {
            return redirect()
                ->route('entreprise.dashboard', ['slug' => session('google_calendar_entreprise_slug'), 'tab' => 'parametres'])
                ->with('error', 'Paramètres manquants dans la réponse Google.');
        }

        $entreprise = Entreprise::findOrFail($entrepriseId);

        // Vérifier que l'utilisateur peut gérer cette entreprise
        if (!$entreprise->peutEtreGereePar(auth()->user())) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        try {
            $this->googleCalendarService->handleCallback($code, $entreprise);

            // Mettre en place le webhook pour la sync bidirectionnelle
            $this->googleCalendarService->setupWatch($entreprise);

            return redirect()
                ->route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'parametres'])
                ->with('success', 'Google Calendar connecté avec succès ! Vos réservations seront synchronisées automatiquement.');
        } catch (\Exception $e) {
            Log::error('Erreur callback Google Calendar : ' . $e->getMessage());

            return redirect()
                ->route('entreprise.dashboard', ['slug' => $entreprise->slug, 'tab' => 'parametres'])
                ->with('error', 'Erreur lors de la connexion à Google Calendar : ' . $e->getMessage());
        }
    }

    /**
     * Déconnecte Google Calendar de l'entreprise.
     */
    public function disconnect(string $slug)
    {
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        if (!$entreprise->peutEtreGereePar(auth()->user())) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        $this->googleCalendarService->disconnect($entreprise);

        return redirect()
            ->route('entreprise.dashboard', ['slug' => $slug, 'tab' => 'parametres'])
            ->with('success', 'Google Calendar déconnecté.');
    }

    /**
     * Reçoit les notifications push de Google Calendar.
     * Google appelle cette route quand un événement change sur un calendrier surveillé.
     */
    public function webhook(Request $request)
    {
        // Vérifier les headers Google
        $channelId = $request->header('X-Goog-Channel-ID');
        $resourceState = $request->header('X-Goog-Resource-State');

        if (!$channelId) {
            return response('Missing channel ID', 400);
        }

        // Ignorer le ping de vérification
        if ($resourceState === 'sync') {
            return response('OK', 200);
        }

        // Trouver l'entreprise associée à ce channel
        $entreprise = Entreprise::where('google_watch_channel_id', $channelId)->first();

        if (!$entreprise) {
            Log::warning('Webhook Google Calendar reçu pour un channel inconnu : ' . $channelId);
            return response('Channel not found', 404);
        }

        // Lancer la synchronisation incrémentale en arrière-plan
        try {
            \App\Jobs\SyncGoogleCalendarChanges::dispatch($entreprise);
        } catch (\Exception $e) {
            Log::error('Erreur dispatch sync Google pour entreprise #' . $entreprise->id . ': ' . $e->getMessage());
        }

        return response('OK', 200);
    }
}
