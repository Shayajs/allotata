<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    /**
     * Afficher la FAQ publique
     */
    public function index(Request $request)
    {
        $categorie = $request->get('categorie');
        $faqs = Faq::getByCategorie($categorie);
        $categories = Faq::getCategories();

        return view('faq.index', compact('faqs', 'categories', 'categorie'));
    }

    /**
     * Liste des FAQs (admin uniquement)
     */
    public function adminIndex()
    {
        $faqs = Faq::orderBy('ordre')->orderBy('id')->get();
        $categories = Faq::getCategories();

        return view('admin.faqs.index', compact('faqs', 'categories'));
    }

    /**
     * Afficher le formulaire de création (admin uniquement)
     */
    public function adminCreate()
    {
        $categories = Faq::getCategories();
        return view('admin.faqs.create', compact('categories'));
    }

    /**
     * Créer une nouvelle FAQ (admin uniquement)
     */
    public function adminStore(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'reponse' => 'required|string|max:5000',
            'categorie' => 'nullable|string|max:100',
            'ordre' => 'nullable|integer|min:0',
            'est_actif' => 'nullable|boolean',
        ]);

        Faq::create([
            'question' => $validated['question'],
            'reponse' => $validated['reponse'],
            'categorie' => $validated['categorie'] ?? null,
            'ordre' => $validated['ordre'] ?? 0,
            'est_actif' => $validated['est_actif'] ?? true,
        ]);

        return redirect()->route('admin.faqs.index')
            ->with('success', 'FAQ créée avec succès.');
    }

    /**
     * Afficher le formulaire d'édition (admin uniquement)
     */
    public function adminEdit(Faq $faq)
    {
        $categories = Faq::getCategories();
        return view('admin.faqs.edit', compact('faq', 'categories'));
    }

    /**
     * Mettre à jour une FAQ (admin uniquement)
     */
    public function adminUpdate(Request $request, Faq $faq)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'reponse' => 'required|string|max:5000',
            'categorie' => 'nullable|string|max:100',
            'ordre' => 'nullable|integer|min:0',
            'est_actif' => 'nullable|boolean',
        ]);

        $faq->update($validated);

        return redirect()->route('admin.faqs.index')
            ->with('success', 'FAQ mise à jour avec succès.');
    }

    /**
     * Supprimer une FAQ (admin uniquement)
     */
    public function adminDestroy(Faq $faq)
    {
        $faq->delete();

        return redirect()->route('admin.faqs.index')
            ->with('success', 'FAQ supprimée avec succès.');
    }
}
