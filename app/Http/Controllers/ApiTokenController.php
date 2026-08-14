<?php

namespace App\Http\Controllers;

use App\Models\ApiToken;
use App\Support\SubdomainHost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Ecran de gestion des jetons personnels d'API (reglages du compte).
 */
class ApiTokenController extends Controller
{
    /** Au-dela, c'est une fuite de jetons oublies plutot qu'un besoin reel. */
    private const MAXIMUM = 10;

    public function index(Request $request): View
    {
        return view('settings.api-tokens', [
            'jetons' => $request->user()->apiTokens()->latest()->get(),
            'documentationUrl' => SubdomainHost::enabled()
                ? SubdomainHost::ownerUrl('/api')
                : url('/api'),
            'maximum' => self::MAXIMUM,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $valide = $request->validate([
            'nom' => ['required', 'string', 'max:60'],
            'expiration_jours' => ['nullable', 'integer', 'min:1', 'max:730'],
        ], [], [
            'nom' => 'nom du jeton',
            'expiration_jours' => 'durée de validité',
        ]);

        $user = $request->user();

        if ($user->apiTokens()->count() >= self::MAXIMUM) {
            return back()->with('error', 'Vous avez atteint la limite de '.self::MAXIMUM.' jetons. Révoquez-en un avant d\'en créer un nouveau.');
        }

        $cree = ApiToken::creerPour(
            $user,
            $valide['nom'],
            isset($valide['expiration_jours']) ? now()->addDays((int) $valide['expiration_jours']) : null,
        );

        // Le jeton en clair ne repassera jamais : il est affiche une fois, puis oublie.
        return back()
            ->with('jeton_cree', $cree['jeton'])
            ->with('success', 'Jeton « '.$cree['modele']->nom.' » créé. Copiez-le maintenant, il ne sera plus affiché.');
    }

    public function destroy(Request $request, int $jeton): RedirectResponse
    {
        $modele = $request->user()->apiTokens()->find($jeton);

        if (! $modele) {
            return back()->with('error', 'Ce jeton n\'existe plus.');
        }

        $nom = $modele->nom;
        $modele->delete();

        return back()->with('success', 'Jeton « '.$nom.' » révoqué. Les appels qui l\'utilisent reçoivent désormais une erreur 401.');
    }
}
