<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\ErrorLog;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleCalendarController extends Controller
{
    public function __construct(
        protected GoogleCalendarService $googleCalendarService
    ) {}

    /**
     * Écrit un log verbose dans la table error_logs (visible dans admin).
     */
    protected function logVerbose(string $level, string $message, ?Request $request = null): void
    {
        try {
            ErrorLog::create([
                'level' => $level,
                'message' => $message,
                'file' => 'GoogleCalendarController',
                'line' => 0,
                'url' => $request?->fullUrl() ?? request()->fullUrl(),
                'method' => $request?->method() ?? 'GET',
                'ip' => $request?->ip() ?? request()->ip(),
                'user_agent' => $request?->userAgent() ?? request()->userAgent(),
                'user_id' => auth()->id(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Impossible d\'écrire le log verbose Google Calendar : ' . $e->getMessage());
        }
    }

    /**
     * Redirige vers l'écran de consentement Google OAuth.
     */
    public function redirect(string $slug)
    {
        $entreprise = Entreprise::where('slug', $slug)->firstOrFail();

        if (!$entreprise->peutEtreGereePar(auth()->user())) {
            abort(403, 'Vous n\'avez pas accès à cette entreprise.');
        }

        // Stocker le slug en session pour le callback
        session(['google_calendar_entreprise_slug' => $slug]);

        // Vérifier que les config sont bien définies
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');
        $redirectUri = config('services.google.redirect_uri');

        $this->logVerbose('info', "[Google Calendar] REDIRECT — Entreprise: {$entreprise->nom} (#{$entreprise->id}), Slug: {$slug}");
        $this->logVerbose('info', "[Google Calendar] CONFIG — client_id: " . ($clientId ? substr($clientId, 0, 20) . '...' : 'NULL') . ", client_secret: " . ($clientSecret ? '***SET***' : 'NULL') . ", redirect_uri: " . ($redirectUri ?: 'NULL'));

        if (!$clientId || !$clientSecret) {
            $this->logVerbose('error', "[Google Calendar] ERREUR CONFIG — Les variables GOOGLE_CLIENT_ID et/ou GOOGLE_CLIENT_SECRET ne sont pas définies dans .env ! Pense à faire config:clear après modification.");
            return redirect()
                ->route('entreprise.dashboard', ['slug' => $slug, 'tab' => 'parametres'])
                ->with('error', 'Configuration Google Calendar incomplète. Vérifiez les variables GOOGLE_CLIENT_ID et GOOGLE_CLIENT_SECRET dans le .env, puis lancez php artisan config:clear.');
        }

        $authUrl = $this->googleCalendarService->getAuthUrl($entreprise);

        $this->logVerbose('info', "[Google Calendar] REDIRECT OK — URL générée, redirection vers Google...");

        return redirect()->away($authUrl);
    }

    /**
     * Traite le retour de Google après le consentement OAuth.
     */
    public function callback(Request $request)
    {
        $this->logVerbose('info', "[Google Calendar] CALLBACK ATTEINT — Params: code=" . ($request->has('code') ? 'OUI' : 'NON') . ", state=" . ($request->has('state') ? $request->get('state') : 'NON') . ", error=" . ($request->get('error', 'NON')) . ", user_id=" . (auth()->id() ?? 'NON CONNECTÉ') . ", session_slug=" . (session('google_calendar_entreprise_slug') ?? 'NULL'), $request);

        $slug = session('google_calendar_entreprise_slug', 'dashboard');

        if ($request->has('error')) {
            $this->logVerbose('warning', "[Google Calendar] CALLBACK ANNULÉ — Erreur Google: " . $request->get('error') . " | Description: " . $request->get('error_description', 'aucune'), $request);
            return redirect()
                ->route('entreprise.dashboard', ['slug' => $slug, 'tab' => 'parametres'])
                ->with('error', 'La connexion à Google Calendar a été annulée.');
        }

        $code = $request->get('code');
        $entrepriseId = $request->get('state');

        if (!$code || !$entrepriseId) {
            $this->logVerbose('error', "[Google Calendar] CALLBACK PARAMS MANQUANTS — code: " . (!empty($code) ? 'OK' : 'MANQUANT') . ", state/entreprise_id: " . (!empty($entrepriseId) ? $entrepriseId : 'MANQUANT'), $request);
            return redirect()
                ->route('entreprise.dashboard', ['slug' => $slug, 'tab' => 'parametres'])
                ->with('error', 'Paramètres manquants dans la réponse Google.');
        }

        try {
            $entreprise = Entreprise::findOrFail($entrepriseId);
            $slug = $entreprise->slug;

            $this->logVerbose('info', "[Google Calendar] ENTREPRISE TROUVÉE — #{$entreprise->id} ({$entreprise->nom})", $request);

            if (!$entreprise->peutEtreGereePar(auth()->user())) {
                $this->logVerbose('error', "[Google Calendar] ACCÈS REFUSÉ — User #" . auth()->id() . " ne peut pas gérer l'entreprise #{$entreprise->id}", $request);
                abort(403, 'Vous n\'avez pas accès à cette entreprise.');
            }

            // Vérifier la config au moment de l'échange
            $clientId = config('services.google.client_id');
            $clientSecret = config('services.google.client_secret');
            $redirectUri = config('services.google.redirect_uri');
            $this->logVerbose('info', "[Google Calendar] CONFIG AU CALLBACK — client_id: " . ($clientId ? substr($clientId, 0, 20) . '...' : 'NULL !!!') . ", client_secret: " . ($clientSecret ? '***SET***' : 'NULL !!!') . ", redirect_uri: " . ($redirectUri ?: 'NULL !!!'), $request);

            $this->logVerbose('info', "[Google Calendar] ÉCHANGE CODE → TOKEN — Envoi du code à Google...", $request);

            $this->googleCalendarService->handleCallback($code, $entreprise);

            // Rafraîchir l'entreprise depuis la BDD pour confirmer la sauvegarde
            $entreprise->refresh();

            $this->logVerbose('info', "[Google Calendar] TOKENS SAUVEGARDÉS — access_token: " . (!empty($entreprise->google_access_token) ? 'OUI' : 'NON') . ", refresh_token: " . (!empty($entreprise->google_refresh_token) ? 'OUI' : 'NON') . ", calendar_id: " . ($entreprise->google_calendar_id ?? 'NULL') . ", aGoogleCalendar(): " . ($entreprise->aGoogleCalendar() ? 'TRUE' : 'FALSE'), $request);

            // Mettre en place le webhook pour la sync bidirectionnelle
            try {
                $this->googleCalendarService->setupWatch($entreprise);
                $this->logVerbose('info', "[Google Calendar] WEBHOOK CONFIGURÉ — channel_id: " . ($entreprise->google_watch_channel_id ?? 'NULL'), $request);
            } catch (\Throwable $e) {
                $this->logVerbose('warning', "[Google Calendar] WEBHOOK ÉCHOUÉ (non bloquant) — " . $e->getMessage(), $request);
            }

            $this->logVerbose('info', "[Google Calendar] SUCCÈS — Connexion terminée, redirection vers le dashboard.", $request);

            return redirect()
                ->route('entreprise.dashboard', ['slug' => $slug, 'tab' => 'parametres'])
                ->with('success', 'Google Calendar connecté avec succès ! Vos réservations seront synchronisées automatiquement.');
        } catch (\Throwable $e) {
            $this->logVerbose('error', "[Google Calendar] ERREUR CALLBACK — " . get_class($e) . ": " . $e->getMessage() . " | Fichier: " . $e->getFile() . ":" . $e->getLine(), $request);

            Log::error('Erreur callback Google Calendar', [
                'message' => $e->getMessage(),
                'exception_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
                'entreprise_id' => $entrepriseId,
            ]);

            return redirect()
                ->route('entreprise.dashboard', ['slug' => $slug, 'tab' => 'parametres'])
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
