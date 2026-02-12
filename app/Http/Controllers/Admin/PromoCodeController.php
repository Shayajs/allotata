<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PromoCodeController extends Controller
{
    /**
     * Liste des codes promo
     */
    public function index()
    {
        $promoCodes = PromoCode::with('createdBy')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.promo-codes.index', compact('promoCodes'));
    }

    /**
     * Formulaire de création
     */
    public function create()
    {
        // Générer un code aléatoire
        $suggestedCode = strtoupper(Str::random(8));
        
        return view('admin.promo-codes.create', compact('suggestedCode'));
    }

    /**
     * Créer un code promo
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:promo_codes,code',
            'description' => 'nullable|string|max:255',
            'type' => 'required|in:pourcentage,montant_fixe',
            'valeur' => 'required|numeric|min:0',
            'usages_max' => 'nullable|integer|min:1',
            'duree_mois' => 'nullable|integer|min:1',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'est_actif' => 'nullable|boolean',
            'premier_abonnement_uniquement' => 'nullable|boolean',
        ]);

        $promoCode = PromoCode::create([
            ...$validated,
            'code' => strtoupper($validated['code']),
            'est_actif' => $validated['est_actif'] ?? true,
            'premier_abonnement_uniquement' => $validated['premier_abonnement_uniquement'] ?? true,
            'created_by' => auth()->id(),
        ]);

        ActivityLog::log('create', "Création du code promo: {$promoCode->code}", $promoCode);

        return redirect()->route('admin.promo-codes.index')
            ->with('success', 'Code promo créé avec succès.');
    }

    /**
     * Afficher un code promo
     */
    public function show(PromoCode $promoCode)
    {
        return view('admin.promo-codes.show', compact('promoCode'));
    }

    /**
     * Formulaire d'édition
     */
    public function edit(PromoCode $promoCode)
    {
        return view('admin.promo-codes.edit', compact('promoCode'));
    }

    /**
     * Mettre à jour un code promo
     */
    public function update(Request $request, PromoCode $promoCode)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:promo_codes,code,' . $promoCode->id,
            'description' => 'nullable|string|max:255',
            'type' => 'required|in:pourcentage,montant_fixe',
            'valeur' => 'required|numeric|min:0',
            'usages_max' => 'nullable|integer|min:1',
            'duree_mois' => 'nullable|integer|min:1',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'est_actif' => 'nullable|boolean',
            'premier_abonnement_uniquement' => 'nullable|boolean',
        ]);

        $promoCode->update([
            ...$validated,
            'code' => strtoupper($validated['code']),
            'est_actif' => $validated['est_actif'] ?? false,
            'premier_abonnement_uniquement' => $validated['premier_abonnement_uniquement'] ?? false,
        ]);

        ActivityLog::log('update', "Modification du code promo: {$promoCode->code}", $promoCode);

        return redirect()->route('admin.promo-codes.index')
            ->with('success', 'Code promo mis à jour.');
    }

    /**
     * Supprimer un code promo
     */
    public function destroy(PromoCode $promoCode)
    {
        $code = $promoCode->code;
        $promoCode->delete();

        ActivityLog::log('delete', "Suppression du code promo: {$code}");

        return redirect()->route('admin.promo-codes.index')
            ->with('success', 'Code promo supprimé.');
    }
}
