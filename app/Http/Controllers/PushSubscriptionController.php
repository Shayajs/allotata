<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushSubscriptionController extends Controller
{
    /**
     * Enregistrer une souscription push (utilisateur authentifié)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'url', 'max:500'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
            'content_encoding' => ['nullable', 'string', 'in:aesgcm,aes128gcm'],
        ]);

        $user = Auth::user();

        // Upsert : si l'endpoint existe déjà pour cet utilisateur, on met à jour les clés
        PushSubscription::updateOrCreate(
            [
                'user_id' => $user->id,
                'endpoint' => $validated['endpoint'],
            ],
            [
                'p256dh_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => $validated['content_encoding'] ?? 'aesgcm',
            ]
        );

        return response()->json(['success' => true, 'message' => 'Souscription push enregistrée.']);
    }

    /**
     * Stocker temporairement une souscription push en session (wizard inscription)
     */
    public function storeGuest(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'url', 'max:500'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
            'content_encoding' => ['nullable', 'string', 'in:aesgcm,aes128gcm'],
        ]);

        // Stocker en session pour rattacher au user après création du compte
        $request->session()->put('pending_push_subscription', [
            'endpoint' => $validated['endpoint'],
            'p256dh_key' => $validated['keys']['p256dh'],
            'auth_token' => $validated['keys']['auth'],
            'content_encoding' => $validated['content_encoding'] ?? 'aesgcm',
        ]);

        return response()->json(['success' => true, 'message' => 'Souscription push stockée temporairement.']);
    }

    /**
     * Supprimer une souscription push
     */
    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'url'],
        ]);

        $user = Auth::user();

        PushSubscription::where('user_id', $user->id)
            ->where('endpoint', $validated['endpoint'])
            ->delete();

        return response()->json(['success' => true, 'message' => 'Souscription push supprimée.']);
    }

    /**
     * Masquer la bannière de notifications push sur le dashboard
     */
    public function dismissBanner(Request $request)
    {
        $user = Auth::user();
        $user->update(['push_banner_dismissed_at' => now()]);

        return response()->json(['success' => true]);
    }
}
